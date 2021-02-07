<?php

namespace platz1de\EasyEdit\task;

use platz1de\EasyEdit\history\HistoryManager;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\ClipBoardManager;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\task\selection\CopyTask;
use platz1de\EasyEdit\task\selection\UndoTask;
use platz1de\EasyEdit\worker\EditWorker;
use platz1de\EasyEdit\worker\WorkerAdapter;
use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\utils\SubChunkIteratorManager;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Server;
use pocketmine\tile\Tile;
use Threaded;
use ThreadedLogger;
use Throwable;

abstract class EditTask extends Threaded
{
	/**
	 * @var bool
	 */
	private $finished = false;

	/**
	 * @var EditWorker
	 */
	protected $worker;

	/**
	 * @var int
	 */
	private $id;

	/**
	 * @var string
	 */
	private $chunkData;
	/**
	 * @var string
	 */
	private $tiles;

	/**
	 * @var string
	 */
	private $selection;
	/**
	 * @var string
	 */
	private $pattern;
	/**
	 * @var string
	 */
	private $place;
	/**
	 * @var string
	 */
	private $level;

	/**
	 * @var string
	 */
	private $result;
	/**
	 * @var string
	 */
	private $toUndo;

	/**
	 * EditTask constructor.
	 * @param Selection $selection
	 * @param Pattern   $pattern
	 * @param Position  $place
	 */
	public function __construct(Selection $selection, Pattern $pattern, Position $place)
	{
		$this->id = WorkerAdapter::getId();
		$chunkData = [];
		$tiles = [];
		foreach ($selection->getNeededChunks($place) as $chunk) {
			$chunkData[] = $chunk->fastSerialize();
			foreach ($chunk->getTiles() as $tile) {
				$tiles[] = $tile->saveNBT();
			}
		}
		$this->chunkData = igbinary_serialize($chunkData);
		$this->tiles = igbinary_serialize($tiles);
		$this->selection = igbinary_serialize($selection);
		$this->pattern = igbinary_serialize($pattern);
		$this->place = igbinary_serialize($place->floor());
		$this->level = $place->getLevelNonNull()->getName();
	}

	public function run(): void
	{
		$start = microtime(true);
		$manager = new ReferencedChunkManager($this->level);
		$iterator = new SubChunkIteratorManager($manager);
		$origin = new SubChunkIteratorManager(clone $manager);
		/** @var Selection $selection */
		$selection = igbinary_unserialize($this->selection);
		/** @var Pattern $pattern */
		$pattern = igbinary_unserialize($this->pattern);
		/** @var Vector3 $place */
		$place = igbinary_unserialize($this->place);

		foreach (array_map(static function (string $chunk) {
			return Chunk::fastDeserialize($chunk);
		}, igbinary_unserialize($this->chunkData)) as $chunk) {
			$iterator->level->setChunk($chunk->getX(), $chunk->getZ(), $chunk);
		}

		foreach (array_map(static function (string $chunk) {
			return Chunk::fastDeserialize($chunk);
		}, igbinary_unserialize($this->chunkData)) as $chunk) {
			$origin->level->setChunk($chunk->getX(), $chunk->getZ(), $chunk);
		}

		$tiles = [];
		/** @var CompoundTag $tile */
		foreach (igbinary_unserialize($this->tiles) as $tile) {
			$tiles[Level::blockHash($tile->getInt(Tile::TAG_X), $tile->getInt(Tile::TAG_Y), $tile->getInt(Tile::TAG_Z))] = $tile;
		}

		$toUndo = $this->getUndoBlockList($selection, $place, $this->level);

		$this->getLogger()->debug("Task " . $this->getTaskName() . ":" . $this->getId() . " loaded " . count($manager->getChunks()) . " Chunks");

		$this->getLogger()->debug("Running Task " . $this->getTaskName() . ":" . $this->getId());

		$changed = 0;

		try {
			$this->execute($iterator, $tiles, $selection, $pattern, $place, $toUndo, $origin, $changed);
			$this->getLogger()->debug("Task " . $this->getTaskName() . ":" . $this->getId() . " was executed successful in " . (microtime(true) - $start) . "s, changing " . $changed . " blocks");

			$result = [];
			$result[] = array_map(static function (Chunk $chunk) {
				return $chunk->fastSerialize();
			}, $manager->getChunks());
			$result[] = $tiles;
			$this->result = igbinary_serialize($result);
			$this->toUndo = igbinary_serialize($toUndo);
		} catch (Throwable $exception) {
			$this->result = igbinary_serialize($exception);
		}
		$this->finished = true;
	}

	/**
	 * @return ThreadedLogger
	 */
	public function getLogger(): ThreadedLogger
	{
		return $this->worker->getLogger();
	}

	/**
	 * @return string
	 */
	abstract public function getTaskName(): string;

	/**
	 * @param SubChunkIteratorManager $iterator
	 * @param CompoundTag[]           $tiles
	 * @param Selection               $selection
	 * @param Pattern                 $pattern
	 * @param Vector3                 $place
	 * @param BlockListSelection      $toUndo also used as return value of Task for things like copy
	 * @param SubChunkIteratorManager $origin original World, used for patterns
	 * @param int                     $changed
	 */
	abstract public function execute(SubChunkIteratorManager $iterator, array &$tiles, Selection $selection, Pattern $pattern, Vector3 $place, BlockListSelection $toUndo, SubChunkIteratorManager $origin, int &$changed): void;

	/**
	 * @return int
	 */
	public function getId(): int
	{
		return $this->id;
	}

	/**
	 * @return bool
	 */
	public function isFinished(): bool
	{
		return $this->finished;
	}

	/**
	 * @return null|ReferencedChunkManager
	 */
	public function getResult(): ?ReferencedChunkManager
	{
		if (isset($this->result)) {
			$result = igbinary_unserialize($this->result);

			if ($result instanceof Throwable) {
				Server::getInstance()->getLogger()->logException($result);
				return null;
			}

			/** @var Selection $selection */
			$selection = igbinary_unserialize($this->selection);

			if ($this instanceof UndoTask) {
				HistoryManager::addToFuture($selection->getPlayer(), igbinary_unserialize($this->toUndo));
			} elseif ($this instanceof CopyTask) {
				ClipBoardManager::setForPlayer($selection->getPlayer(), igbinary_unserialize($this->toUndo));
				return null;
			} else {
				HistoryManager::addToHistory($selection->getPlayer(), igbinary_unserialize($this->toUndo));
			}

			$manager = new ReferencedChunkManager($this->level);
			foreach (array_map(static function (string $chunk) {
				return Chunk::fastDeserialize($chunk);
			}, $result[0]) as $chunk) {
				$manager->setChunk($chunk->getX(), $chunk->getZ(), $chunk);
			}
			foreach ($manager->getChunks() as $chunk) {
				$c = $manager->getLevel()->getChunk($chunk->getX(), $chunk->getZ());
				if ($c === null) {
					continue;
				}
				foreach ($c->getTiles() as $tile) {
					$tile->close();
				}
			}
			/** @var CompoundTag $data */
			foreach ($result[1] as $data) {
				Tile::createTile($data->getString(Tile::TAG_ID), $manager->getLevel(), $data);
			}
			return $manager;
		}

		return null;
	}

	/**
	 * @param Selection $selection
	 * @param Vector3   $place
	 * @param string    $level
	 * @return BlockListSelection
	 */
	abstract public function getUndoBlockList(Selection $selection, Vector3 $place, string $level): BlockListSelection;
}