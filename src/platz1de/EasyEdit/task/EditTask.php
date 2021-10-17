<?php

namespace platz1de\EasyEdit\task;

use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\thread\EditAdapter;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\thread\input\ChunkInputData;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\thread\output\ChunkRequestData;
use platz1de\EasyEdit\thread\ThreadData;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\HeightMapCache;
use platz1de\EasyEdit\utils\TaskCache;
use pocketmine\math\Vector3;
use pocketmine\world\Position;
use pocketmine\world\World;
use Thread;
use ThreadedLogger;
use Throwable;
use UnexpectedValueException;

abstract class EditTask
{
	private bool $finished = false;

	protected EditThread $worker;
	private int $id;
	private Selection $selection;
	private Pattern $pattern;
	private Vector3 $place;
	private string $world;
	private EditTaskResult $result;
	private int $changeId = -1;
	private AdditionalDataManager $data;
	private Selection $total;

	/**
	 * EditTask constructor.
	 * @param Selection             $selection
	 * @param Pattern               $pattern
	 * @param string                $world
	 * @param Position              $place
	 * @param AdditionalDataManager $data
	 * @param Selection|null        $total Initial Selection
	 */
	public function __construct(Selection $selection, Pattern $pattern, string $world, Vector3 $place, AdditionalDataManager $data, ?Selection $total = null)
	{
		EditThread::getInstance()->setStatus(EditThread::STATUS_PREPARING);
		$this->id = EditAdapter::getId();
		$this->selection = $selection;
		$this->pattern = $pattern;
		$this->place = $place->floor();
		$this->world = $world;
		if ($total instanceof Selection) {
			$this->total = $total;
		}
		$this->data = $data;

		EditThread::getInstance()->sendOutput(ChunkRequestData::from($selection->getNeededChunks($place), $world));
	}

	public function checkData(): void
	{
		if (!$this->finished) {
			$data = ThreadData::getStoredData();
			if ($data !== null) {
				$this->run($data);
			}
		}
	}

	public function run(ChunkInputData $chunkData): void
	{
		$start = microtime(true);
		/** @var EditThread $thread */
		$thread = Thread::getCurrentThread();
		$thread->setStatus(EditThread::STATUS_RUNNING);

		try {
			if (isset($this->total)) {
				TaskCache::init($this->total);
			} elseif ($this->data->isFirstPiece()) {
				throw new UnexpectedValueException("Initial editing piece passed no selection as parameter");
			}

			$handler = EditTaskHandler::fromData($this->world, $chunkData->getChunkData(), $chunkData->getTileData(), $this->getUndoBlockList(TaskCache::getFullSelection(), $this->place, $this->world, $this->data), $this->data, $this->pattern);

			$this->getLogger()->debug("Task " . $this->getTaskName() . ":" . $this->getId() . " loaded " . $handler->getChunkCount() . " Chunks, Context: " . $handler->getSelectionContext()->getName());

			HeightMapCache::prepare();

			$this->execute($handler, $this->selection, $this->place, $this->data);
			$this->getLogger()->debug("Task " . $this->getTaskName() . ":" . $this->getId() . " was executed successful in " . (microtime(true) - $start) . "s, changing " . $handler->getChangedBlockCount() . " blocks (" . $handler->getReadBlockCount() . " read, " . $handler->getWrittenBlockCount() . " written, " . $handler->getChangedTileCount() . " affected tiles)");

			if ($this->data->isSavingUndo()) {
				StorageModule::collect($handler->getChanges());
			}
			$result = new EditTaskResult($this->world, $handler->getTiles(), microtime(true) - $start, $handler->getChangedBlockCount());

			foreach ($handler->getResult()->getChunks() as $hash => $chunk) {
				World::getXZ($hash, $x, $z);
				//separate chunks which are only loaded for patterns
				if ($this->selection->isChunkOfSelection($x, $z, $this->place)) {
					$result->addChunk($x, $z, $chunk);
				}
			}

			$this->result = $result;

			if ($this->data->isFinalPiece() && $this->data->isSavingUndo()) {
				$this->changeId = StorageModule::finishCollecting();
			}
		} catch (Throwable $exception) {
			$this->getLogger()->logException($exception);
			unset($this->result);
		}

		if ($this->data->isFinalPiece()) {
			TaskCache::clear();
		}

		$this->finished = true;
		$thread->setStatus(EditThread::STATUS_IDLE);
	}

	/**
	 * @return ThreadedLogger
	 */
	public function getLogger(): ThreadedLogger
	{
		return EditThread::getInstance()->getLogger();
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
		$this->checkData();
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
		return $this->result ?? null;
	}

	/**
	 * @return null|AdditionalDataManager
	 */
	public function getAdditionalData(): ?AdditionalDataManager
	{
		return $this->data ?? null;
	}

	/**
	 * @return int
	 */
	public function getChangeId(): int
	{
		return $this->changeId;
	}

	/**
	 * @param string                $player
	 * @param float                 $time
	 * @param string                $changed
	 * @param AdditionalDataManager $data
	 */
	abstract public static function notifyUser(string $player, float $time, string $changed, AdditionalDataManager $data): void;

	/**
	 * @param Selection             $selection
	 * @param Vector3               $place
	 * @param string                $world
	 * @param AdditionalDataManager $data
	 * @return BlockListSelection
	 */
	abstract public function getUndoBlockList(Selection $selection, Vector3 $place, string $world, AdditionalDataManager $data): BlockListSelection;
}