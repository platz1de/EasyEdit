<?php
/**
 * pthreads returns null for undefined properties, so we have to use normal ones
 * @noinspection PhpMissingFieldTypeInspection
 */

namespace platz1de\EasyEdit\task;

use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\HeightMapCache;
use platz1de\EasyEdit\utils\LoaderManager;
use platz1de\EasyEdit\utils\TaskCache;
use platz1de\EasyEdit\worker\EditWorker;
use platz1de\EasyEdit\worker\WorkerAdapter;
use pocketmine\math\Vector3;
use pocketmine\world\format\io\ChunkData;
use pocketmine\world\format\io\FastChunkSerializer;
use pocketmine\world\Position;
use pocketmine\world\World;
use Thread;
use Threaded;
use ThreadedLogger;
use Throwable;
use UnexpectedValueException;

abstract class EditTask extends Threaded
{
	private bool $finished = false;

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
	private $world;
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
		EasyEdit::getWorker()->setStatus(EditWorker::STATUS_PREPARING);
		$this->id = WorkerAdapter::getId();
		$this->selection = $selection->fastSerialize();
		$this->pattern = $pattern->fastSerialize();
		$this->place = igbinary_serialize($place->floor());
		$this->world = $place->getWorld()->getFolderName();
		if ($total instanceof Selection) {
			$this->total = $total->fastSerialize();
		}
		$this->data = igbinary_serialize($data);

		$this->prepareNextChunk($selection->getNeededChunks($place), $place->getWorld(), new ExtendedBinaryStream(), new ExtendedBinaryStream());
	}

	/**
	 * @param int[]                $chunks
	 * @param World                $world
	 * @param ExtendedBinaryStream $chunkData
	 * @param ExtendedBinaryStream $tileData
	 */
	private function prepareNextChunk(array $chunks, World $world, ExtendedBinaryStream $chunkData, ExtendedBinaryStream $tileData): void
	{
		World::getXZ((int) array_pop($chunks), $x, $z);

		$world->orderChunkPopulation($x, $z, null)->onCompletion(
			function () use ($tileData, $chunkData, $z, $x, $world, $chunks): void {
				$chunkData->putInt($x);
				$chunkData->putInt($z);
				$chunk = LoaderManager::getChunk($world, $x, $z);
				if ($chunk instanceof ChunkData) {
					foreach ($chunk->getTileNBT() as $tile) {
						$tileData->putCompound($tile);
					}

					$chunk = $chunk->getChunk();
				} else {
					foreach ($chunk->getTiles() as $tile) {
						$tileData->putCompound($tile->saveNBT());
					}
				}
				$chunkData->putString(FastChunkSerializer::serializeWithoutLight($chunk));

				if ($chunks === []) {
					$this->chunkData = $chunkData->getBuffer();
					$this->tileData = $tileData->getBuffer();
					EasyEdit::getWorker()->stack($this);
				} else {
					$this->prepareNextChunk($chunks, $world, $chunkData, $tileData);
				}
			},
			function () use ($z, $x): void {
				throw new UnexpectedValueException("Failed to prepare Chunk " . $x . " " . $z);
			}
		);
	}

	public function run(): void
	{
		$start = microtime(true);
		/** @var EditWorker $thread */
		$thread = Thread::getCurrentThread();
		$thread->setStatus(EditWorker::STATUS_RUNNING);
		$selection = Selection::fastDeserialize($this->selection);
		$pattern = Pattern::fastDeserialize($this->pattern);
		/** @var Vector3 $place */
		$place = igbinary_unserialize($this->place);
		/** @var AdditionalDataManager $data */
		$data = igbinary_unserialize($this->data);
		if ($this->total !== null) {
			TaskCache::init(Selection::fastDeserialize($this->total));
		} elseif ($data->getBoolKeyed("firstPiece")) {
			throw new UnexpectedValueException("Initial editing piece passed no selection as parameter");
		}

		$handler = EditTaskHandler::fromData($this->world, $this->chunkData, $this->tileData, $this->getUndoBlockList(TaskCache::getFullSelection(), $place, $this->world, $data), $data, $pattern);

		$this->getLogger()->debug("Task " . $this->getTaskName() . ":" . $this->getId() . " loaded " . $handler->getChunkCount() . " Chunks, Context: " . $handler->getSelectionContext()->getName());

		HeightMapCache::prepare();

		try {
			$this->execute($handler, $selection, $place, $data);
			$this->getLogger()->debug("Task " . $this->getTaskName() . ":" . $this->getId() . " was executed successful in " . (microtime(true) - $start) . "s, changing " . $handler->getChangedBlockCount() . " blocks");

			$result = new EditTaskResult($this->world, $handler->getChanges(), $handler->getTiles(), microtime(true) - $start, $handler->getChangedBlockCount());

			foreach ($handler->getResult()->getChunks() as $hash => $chunk) {
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
		$thread->setStatus(EditWorker::STATUS_IDLE);

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
	 * @param EditTaskHandler       $handler
	 * @param Selection             $selection
	 * @param Vector3               $place
	 * @param AdditionalDataManager $data
	 */
	abstract public function execute(EditTaskHandler $handler, Selection $selection, Vector3 $place, AdditionalDataManager $data): void;

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
		if ($this->result !== null) {
			return EditTaskResult::fastDeserialize($this->result);
		}

		return null;
	}

	/**
	 * @return null|AdditionalDataManager
	 */
	public function getAdditionalData(): ?AdditionalDataManager
	{
		if ($this->data !== null) {
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
	 * @param string                $world
	 * @param AdditionalDataManager $data
	 * @return BlockListSelection
	 */
	abstract public function getUndoBlockList(Selection $selection, Vector3 $place, string $world, AdditionalDataManager $data): BlockListSelection;
}