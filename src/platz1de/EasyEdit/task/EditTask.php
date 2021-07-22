<?php

namespace platz1de\EasyEdit\task;

use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\HeightMapCache;
use platz1de\EasyEdit\utils\LoaderManager;
use platz1de\EasyEdit\utils\SafeSubChunkIteratorManager;
use platz1de\EasyEdit\utils\TaskCache;
use platz1de\EasyEdit\utils\TileUtils;
use platz1de\EasyEdit\worker\EditWorker;
use platz1de\EasyEdit\worker\WorkerAdapter;
use pocketmine\level\format\Chunk;
use pocketmine\level\generator\Generator;
use pocketmine\level\generator\GeneratorManager;
use pocketmine\world\World;
use pocketmine\world\Position;
use pocketmine\math\Vector3;
use pocketmine\nbt\LittleEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\tile\Tile;
use pocketmine\utils\Random;
use Thread;
use Threaded;
use ThreadedLogger;
use Throwable;
use UnexpectedValueException;

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
	private $tileData;

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
	private $data;
	/**
	 * @var string
	 */
	private $total;
	/**
	 * @var int
	 */
	private $seed;
	/**
	 * @var string
	 */
	private $generatorClass;
	/**
	 * @var string
	 */
	private $settings;

	/**
	 * EditTask constructor.
	 * @param Selection             $selection
	 * @param Pattern               $pattern
	 * @param Position              $place
	 * @param AdditionalDataManager $data
	 * @param Selection|null        $total Initial Selection
	 */
	public function __construct(Selection $selection, Pattern $pattern, Position $place, AdditionalDataManager $data, ?Selection $total = null)
	{
		$this->id = WorkerAdapter::getId();
		$chunkData = new ExtendedBinaryStream();
		$tileData = new LittleEndianNBTStream();
		foreach ($selection->getNeededChunks($place) as $chunk) {
			$chunkData->putString($chunk->fastSerialize());

			if (LoaderManager::isChunkInit($chunk)) {
				foreach ($chunk->getTiles() as $tile) {
					$tileData->writeTag($tile->saveNBT());
				}
			} else {
				$tileData->write(TileUtils::loadFrom($chunk));
			}
		}
		$this->chunkData = $chunkData->getBuffer();
		$this->tileData = $tileData->buffer;
		$this->selection = $selection->fastSerialize();
		$this->pattern = igbinary_serialize($pattern);
		$this->place = igbinary_serialize($place->floor());
		$this->level = $place->getLevelNonNull()->getFolderName();
		if ($total instanceof Selection) {
			$this->total = $total->fastSerialize();
		}
		$this->data = igbinary_serialize($data);
		$this->generatorClass = GeneratorManager::getGenerator($place->getLevelNonNull()->getProvider()->getGenerator());
		$this->settings = igbinary_serialize($place->getLevelNonNull()->getProvider()->getGeneratorOptions());
		$this->seed = $place->getLevelNonNull()->getSeed();
	}

	public function run(): void
	{
		$start = microtime(true);
		/** @var EditWorker $thread */
		$thread = Thread::getCurrentThread();
		$thread->setRunning();
		$manager = new ReferencedChunkManager($this->level, $this->seed);
		$iterator = new SafeSubChunkIteratorManager($manager);
		$origin = new SafeSubChunkIteratorManager(clone $manager);
		$selection = Selection::fastDeserialize($this->selection);
		/** @var Pattern $pattern */
		$pattern = igbinary_unserialize($this->pattern);
		/** @var Vector3 $place */
		$place = igbinary_unserialize($this->place);
		/** @var AdditionalDataManager $data */
		$data = igbinary_unserialize($this->data);
		if (isset($this->total)) {
			TaskCache::init(Selection::fastDeserialize($this->total));
		} elseif ($data->getBoolKeyed("firstPiece")) {
			throw new UnexpectedValueException("Initial editing piece passed no selection as parameter");
		}

		//TODO: Maybe actually only plant full trees or sth?
		/**
		 * @var Generator $generator
		 */
		$generator = new $this->generatorClass(igbinary_unserialize($this->settings));
		$generator->init($manager, new Random($this->seed));

		$chunkData = new ExtendedBinaryStream($this->chunkData);
		while (!$chunkData->feof()) {
			$chunk = Chunk::fastDeserialize($chunkData->getString());

			$iterator->level->setChunk($chunk->getX(), $chunk->getZ(), $chunk);

			if (!$chunk->isGenerated()) {
				$generator->generateChunk($chunk->getX(), $chunk->getZ());
			}

			if (!$chunk->isPopulated()) {
				$generator->populateChunk($chunk->getX(), $chunk->getZ());
			}

			//separate chunks which are only loaded for patterns
			if ($selection->isChunkOfSelection($chunk->getX(), $chunk->getZ(), $place)) {
				$chunk->setChanged(); //TODO: add a proper separation of core and data chunks
			}

			$origin->level->setChunk($chunk->getX(), $chunk->getZ(), LoaderManager::cloneChunk($chunk));
		}

		$tiles = [];
		if ($this->tileData !== "") {
			$tileData = (new LittleEndianNBTStream())->read($this->tileData, true);
			/** @var CompoundTag $tile */
			foreach (is_array($tileData) ? $tileData : [$tileData] as $tile) {
				$tiles[Level::blockHash($tile->getInt(Tile::TAG_X), $tile->getInt(Tile::TAG_Y), $tile->getInt(Tile::TAG_Z))] = $tile;
			}
		}

		$toUndo = $this->getUndoBlockList(TaskCache::getFullSelection(), $place, $this->level, $data);

		$this->getLogger()->debug("Task " . $this->getTaskName() . ":" . $this->getId() . " loaded " . count($manager->getChunks()) . " Chunks");

		$changed = 0;

		HeightMapCache::prepare();

		try {
			$this->execute($iterator, $tiles, $selection, $pattern, $place, $toUndo, $origin, $changed, $data);
			$this->getLogger()->debug("Task " . $this->getTaskName() . ":" . $this->getId() . " was executed successful in " . (microtime(true) - $start) . "s, changing " . $changed . " blocks");

			$result = new EditTaskResult($this->level, $toUndo, $tiles, microtime(true) - $start, $changed);

			foreach ($manager->getChunks() as $chunk) {
				if ($chunk->hasChanged()) {
					$chunk->setGenerated();
					$chunk->setPopulated();

					$result->addChunk($chunk);
				}
			}

			$this->result = $result->fastSerialize();

			$this->data = igbinary_serialize($data);
		} catch (Throwable $exception) {
			$this->getLogger()->logException($exception);
			unset($this->result);
		}
		$this->finished = true;
		$thread->setRunning(false);

		if ($data->getBoolKeyed("finalPiece")) {
			TaskCache::clear();
		}
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
	 * @param SafeSubChunkIteratorManager $iterator
	 * @param CompoundTag[]               $tiles
	 * @param Selection                   $selection
	 * @param Pattern                     $pattern
	 * @param Vector3                     $place
	 * @param BlockListSelection          $toUndo also used as return value of Task for things like copy
	 * @param SafeSubChunkIteratorManager $origin original World, used for patterns
	 * @param int                         $changed
	 * @param AdditionalDataManager       $data
	 */
	abstract public function execute(SafeSubChunkIteratorManager $iterator, array &$tiles, Selection $selection, Pattern $pattern, Vector3 $place, BlockListSelection $toUndo, SafeSubChunkIteratorManager $origin, int &$changed, AdditionalDataManager $data): void;

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
	 * @return bool
	 */
	public function isGarbage(): bool
	{
		return $this->isFinished();
	}

	/**
	 * @return null|EditTaskResult
	 */
	public function getResult(): ?EditTaskResult
	{
		if (isset($this->result)) {
			return EditTaskResult::fastDeserialize($this->result);
		}

		return null;
	}

	/**
	 * @return null|AdditionalDataManager
	 */
	public function getAdditionalData(): ?AdditionalDataManager
	{
		if (isset($this->data)) {
			return igbinary_unserialize($this->data);
		}

		return null;
	}

	/**
	 * @param Selection             $selection
	 * @param float                 $time
	 * @param string                $changed
	 * @param AdditionalDataManager $data
	 */
	abstract public function notifyUser(Selection $selection, float $time, string $changed, AdditionalDataManager $data): void;

	/**
	 * @param Selection             $selection
	 * @param Vector3               $place
	 * @param string                $level
	 * @param AdditionalDataManager $data
	 * @return BlockListSelection
	 */
	abstract public function getUndoBlockList(Selection $selection, Vector3 $place, string $level, AdditionalDataManager $data): BlockListSelection;
}