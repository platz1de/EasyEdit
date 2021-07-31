<?php

namespace platz1de\EasyEdit\task;

use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\HeightMapCache;
use platz1de\EasyEdit\utils\LoaderManager;
use platz1de\EasyEdit\utils\SafeSubChunkExplorer;
use platz1de\EasyEdit\utils\TaskCache;
use platz1de\EasyEdit\worker\EditWorker;
use platz1de\EasyEdit\worker\WorkerAdapter;
use pocketmine\block\tile\Tile;
use pocketmine\math\Vector3;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\FastChunkSerializer;
use pocketmine\world\generator\Generator;
use pocketmine\world\generator\GeneratorManager;
use pocketmine\world\Position;
use pocketmine\world\World;
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
		$tileData = new ExtendedBinaryStream();
		foreach ($selection->getNeededChunks($place) as $hash) {
			World::getXZ($hash, $x, $z);
			$chunkData->putInt($x);
			$chunkData->putInt($z);

			$chunk = LoaderManager::getChunk($place->getWorld(), $x, $z);
			$chunkData->putString(FastChunkSerializer::serializeWithoutLight($chunk));

			foreach ($chunk->getNBTtiles() as $tile) {
				$tileData->putString((new LittleEndianNbtSerializer())->write(new TreeRoot($tile)));
			}
		}
		$this->chunkData = $chunkData->getBuffer();
		$this->tileData = $tileData->getBuffer();
		$this->selection = $selection->fastSerialize();
		$this->pattern = $pattern->fastSerialize();
		$this->place = igbinary_serialize($place->floor());
		$this->level = $place->getWorld()->getFolderName();
		if ($total instanceof Selection) {
			$this->total = $total->fastSerialize();
		}
		$this->data = igbinary_serialize($data);
	}

	public function run(): void
	{
		$start = microtime(true);
		/** @var EditWorker $thread */
		$thread = Thread::getCurrentThread();
		$thread->setRunning();
		$manager = new ReferencedChunkManager($this->level);
		$iterator = new SafeSubChunkExplorer($manager);
		$origin = new SafeSubChunkExplorer($originManager = clone $manager);
		$selection = Selection::fastDeserialize($this->selection);
		$pattern = Pattern::fastDeserialize($this->pattern);
		/** @var Vector3 $place */
		$place = igbinary_unserialize($this->place);
		/** @var AdditionalDataManager $data */
		$data = igbinary_unserialize($this->data);
		if (isset($this->total)) {
			TaskCache::init(Selection::fastDeserialize($this->total));
		} elseif ($data->getBoolKeyed("firstPiece")) {
			throw new UnexpectedValueException("Initial editing piece passed no selection as parameter");
		}

		$chunkData = new ExtendedBinaryStream($this->chunkData);
		while (!$chunkData->feof()) {
			$manager->setChunk($x = $chunkData->getInt(), $z = $chunkData->getInt(), $chunk = FastChunkSerializer::deserialize($chunkData->getString()));

			$originManager->setChunk($x, $z, clone $chunk);
		}

		$tileData = new ExtendedBinaryStream($this->tileData);
		$tiles = [];
		while (!$tileData->feof()) {
			$tile = (new LittleEndianNbtSerializer())->read($tileData->getString())->mustGetCompoundTag();
			$tiles[World::blockHash($tile->getInt(Tile::TAG_X), $tile->getInt(Tile::TAG_Y), $tile->getInt(Tile::TAG_Z))] = $tile;
		}

		$toUndo = $this->getUndoBlockList(TaskCache::getFullSelection(), $place, $this->level, $data);

		$this->getLogger()->debug("Task " . $this->getTaskName() . ":" . $this->getId() . " loaded " . count($manager->getChunks()) . " Chunks");

		$changed = 0;

		HeightMapCache::prepare();

		try {
			$this->execute($iterator, $tiles, $selection, $pattern, $place, $toUndo, $origin, $changed, $data);
			$this->getLogger()->debug("Task " . $this->getTaskName() . ":" . $this->getId() . " was executed successful in " . (microtime(true) - $start) . "s, changing " . $changed . " blocks");

			$result = new EditTaskResult($this->level, $toUndo, $tiles, microtime(true) - $start, $changed);

			foreach ($manager->getChunks() as $hash => $chunk) {
				World::getXZ($hash, $x, $z);
				//separate chunks which are only loaded for patterns
				if ($selection->isChunkOfSelection($x, $z, $place)) {
					$result->addChunk($x, $z, $chunk);
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
	 * @param SafeSubChunkExplorer  $iterator
	 * @param CompoundTag[]         $tiles
	 * @param Selection             $selection
	 * @param Pattern               $pattern
	 * @param Vector3               $place
	 * @param BlockListSelection    $toUndo also used as return value of Task for things like copy
	 * @param SafeSubChunkExplorer  $origin original World, used for patterns
	 * @param int                   $changed
	 * @param AdditionalDataManager $data
	 */
	abstract public function execute(SafeSubChunkExplorer $iterator, array &$tiles, Selection $selection, Pattern $pattern, Vector3 $place, BlockListSelection $toUndo, SafeSubChunkExplorer $origin, int &$changed, AdditionalDataManager $data): void;

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