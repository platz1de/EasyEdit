<?php

namespace platz1de\EasyEdit\task;

use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\HeightMapCache;
use platz1de\EasyEdit\utils\TaskCache;
use platz1de\EasyEdit\utils\TileUtils;
use platz1de\EasyEdit\worker\EditWorker;
use platz1de\EasyEdit\worker\WorkerAdapter;
use pocketmine\level\format\Chunk;
use pocketmine\level\generator\Generator;
use pocketmine\level\generator\GeneratorManager;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\utils\SubChunkIteratorManager;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\tile\Tile;
use pocketmine\utils\Random;
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
	private $data;
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
	 * @param Selection                     $selection
	 * @param Pattern                       $pattern
	 * @param Position                      $place
	 * @param AdditionalDataManager         $data
	 * @param EditTaskResult|Selection|null $previous Initially Selection, for later pieces EditTaskResult
	 */
	public function __construct(Selection $selection, Pattern $pattern, Position $place, AdditionalDataManager $data, $previous = null)
	{
		$this->id = WorkerAdapter::getId();
		$chunkData = [];
		$tiles = [];
		foreach ($selection->getNeededChunks($place) as $chunk) {
			$chunkData[] = $chunk->fastSerialize();
			foreach ($chunk->getTiles() as $tile) {
				$tiles[] = $tile->saveNBT();
			}
			foreach (TileUtils::loadFrom($chunk) as $tile) {
				$tiles[] = $tile;
			}
		}
		$this->chunkData = igbinary_serialize($chunkData);
		$this->tiles = igbinary_serialize($tiles);
		$this->selection = igbinary_serialize($selection);
		$this->pattern = igbinary_serialize($pattern);
		$this->place = igbinary_serialize($place->floor());
		$this->level = $place->getLevelNonNull()->getFolderName();
		if ($previous !== null) {
			$this->result = igbinary_serialize($previous);
		}
		$this->data = igbinary_serialize($data);
		$this->generatorClass = GeneratorManager::getGenerator($place->getLevelNonNull()->getProvider()->getGenerator());
		$this->settings = igbinary_serialize($place->getLevelNonNull()->getProvider()->getGeneratorOptions());
		$this->seed = $place->getLevelNonNull()->getSeed();
	}

	public function run(): void
	{
		$start = microtime(true);
		$manager = new ReferencedChunkManager($this->level, $this->seed);
		$iterator = new SubChunkIteratorManager($manager);
		$origin = new SubChunkIteratorManager(clone $manager);
		/** @var Selection $selection */
		$selection = igbinary_unserialize($this->selection);
		/** @var Pattern $pattern */
		$pattern = igbinary_unserialize($this->pattern);
		/** @var Vector3 $place */
		$place = igbinary_unserialize($this->place);
		/** @var AdditionalDataManager $data */
		$data = igbinary_unserialize($this->data);

		//TODO: Maybe actually only plant full trees or sth?
		/**
		 * @var Generator $generator
		 */
		$generator = new $this->generatorClass(igbinary_unserialize($this->settings));
		$generator->init($manager, new Random($this->seed));

		foreach (array_map(static function (string $chunk) {
			return Chunk::fastDeserialize($chunk);
		}, igbinary_unserialize($this->chunkData)) as $chunk) {
			$iterator->level->setChunk($chunk->getX(), $chunk->getZ(), $chunk);

			if (!$chunk->isGenerated()) {
				$generator->generateChunk($chunk->getX(), $chunk->getZ());
			}

			if (!$chunk->isPopulated()) {
				$generator->populateChunk($chunk->getX(), $chunk->getZ());
			}
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

		$previous = igbinary_unserialize($this->result ?? null);

		if($data->getDataKeyed("firstPiece")) {
			if ($previous instanceof Selection) {
				TaskCache::init($previous);
			}else{
				throw new UnexpectedValueException("Initial editing piece passed no selection as parameter");
			}
		}

		$toUndo = $previous instanceof EditTaskResult ? $previous->getUndo() : $this->getUndoBlockList($previous instanceof Selection ? $previous : $selection, $place, $this->level, $data);

		$this->getLogger()->debug("Task " . $this->getTaskName() . ":" . $this->getId() . " loaded " . count($manager->getChunks()) . " Chunks");

		$changed = 0;

		HeightMapCache::prepare();

		try {
			$this->execute($iterator, $tiles, $selection, $pattern, $place, $toUndo, $origin, $changed, $data);
			$this->getLogger()->debug("Task " . $this->getTaskName() . ":" . $this->getId() . " was executed successful in " . (microtime(true) - $start) . "s, changing " . $changed . " blocks");

			$result = new EditTaskResult($this->level, $toUndo, $tiles, microtime(true) - $start, $changed);

			foreach ($manager->getChunks() as $chunk) {
				$chunk->setGenerated();
				$chunk->setPopulated();

				$result->addChunk($chunk);
			}

			if ($previous instanceof EditTaskResult) {
				$result->merge($previous);
			}

			$this->result = igbinary_serialize($result);

			$this->data = igbinary_serialize($data);
		} catch (Throwable $exception) {
			$this->getLogger()->logException($exception);
			unset($this->result);
		}
		$this->finished = true;

		if($data->getDataKeyed("finalPiece", false)){
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
	 * @param SubChunkIteratorManager $iterator
	 * @param CompoundTag[]           $tiles
	 * @param Selection               $selection
	 * @param Pattern                 $pattern
	 * @param Vector3                 $place
	 * @param BlockListSelection      $toUndo also used as return value of Task for things like copy
	 * @param SubChunkIteratorManager $origin original World, used for patterns
	 * @param int                     $changed
	 * @param AdditionalDataManager   $data
	 */
	abstract public function execute(SubChunkIteratorManager $iterator, array &$tiles, Selection $selection, Pattern $pattern, Vector3 $place, BlockListSelection $toUndo, SubChunkIteratorManager $origin, int &$changed, AdditionalDataManager $data): void;

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
			return igbinary_unserialize($this->result);
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
	 * @param int                   $changed
	 * @param AdditionalDataManager $data
	 */
	abstract public function notifyUser(Selection $selection, float $time, int $changed, AdditionalDataManager $data): void;

	/**
	 * @param Selection             $selection
	 * @param Vector3               $place
	 * @param string                $level
	 * @param AdditionalDataManager $data
	 * @return BlockListSelection
	 */
	abstract public function getUndoBlockList(Selection $selection, Vector3 $place, string $level, AdditionalDataManager $data): BlockListSelection;
}