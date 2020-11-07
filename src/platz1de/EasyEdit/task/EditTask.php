<?php

namespace platz1de\EasyEdit\task;

use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\worker\EditWorker;
use platz1de\EasyEdit\worker\WorkerAdapter;
use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\utils\SubChunkIteratorManager;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
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
	private $result;
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
		$this->place = igbinary_serialize($place->asVector3());
		$this->level = $place->getLevelNonNull()->getName();
	}

	public function run(): void
	{
		$start = microtime(true);
		$iterator = new SubChunkIteratorManager(new ReferencedChunkManager($this->level));
		$selection = igbinary_unserialize($this->selection);
		$pattern = igbinary_unserialize($this->pattern);
		$place = igbinary_unserialize($this->place);

		foreach (array_map(static function (string $chunk) {
			return Chunk::fastDeserialize($chunk);
		}, igbinary_unserialize($this->chunkData)) as $chunk) {
			$iterator->level->setChunk($chunk->getX(), $chunk->getZ(), $chunk);
		}

		$tiles = [];
		/** @var CompoundTag $tile */
		foreach (igbinary_unserialize($this->tiles) as $tile) {
			$tiles[Level::blockHash($tile->getInt(Tile::TAG_X), $tile->getInt(Tile::TAG_Y), $tile->getInt(Tile::TAG_Z))] = $tile;
		}

		$this->getLogger()->debug("Task " . $this->getTaskName() . ":" . $this->getId() . " loaded " . count($iterator->level->getChunks()) . " Chunks");

		$this->getLogger()->debug("Running Task " . $this->getTaskName() . ":" . $this->getId());

		try {
			$this->execute($iterator, $tiles, $selection, $pattern);
			$this->getLogger()->debug("Task " . $this->getTaskName() . ":" . $this->getId() . " was executed successful in " . (microtime(true) - $start) . "s");

			$result = [];
			$result[] = array_map(static function (Chunk $chunk) {
				return $chunk->fastSerialize();
			}, $iterator->level->getChunks());
			$result[] = $tiles;
			$this->result = igbinary_serialize($result);
		} catch (Throwable $exception) {
			$this->getLogger()->logException($exception);
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
	 * @param SubChunkIteratorManager $chunkManager
	 * @param CompoundTag[]           $tiles
	 * @param Selection               $selection
	 * @param Pattern                 $pattern
	 */
	abstract public function execute(SubChunkIteratorManager $chunkManager, array &$tiles, Selection $selection, Pattern $pattern): void;

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
}