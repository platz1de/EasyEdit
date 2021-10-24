<?php

namespace platz1de\EasyEdit\task\queued;

use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\LinkedBlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\task\EditTask;
use platz1de\EasyEdit\task\EditTaskResult;
use platz1de\EasyEdit\task\selection\RedoTask;
use platz1de\EasyEdit\task\selection\UndoTask;
use platz1de\EasyEdit\thread\output\ResultingChunkData;
use platz1de\EasyEdit\thread\output\TaskResultData;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\ReferencedWorldHolder;
use pocketmine\math\Vector3;
use UnexpectedValueException;

class QueuedEditTask
{
	use ReferencedWorldHolder;

	private Selection $selection;
	private Pattern $pattern;
	private Vector3 $place;
	private string $task;
	private AdditionalDataManager $data;
	private Vector3 $splitOffset;
	/**
	 * @var Selection[]
	 */
	private array $pieces;
	private EditTask $currentPiece;
	private int $totalLength;
	private EditTaskResult $result;

	/**
	 * QueuedTask constructor.
	 * @param Selection             $selection
	 * @param Pattern               $pattern
	 * @param string                $world
	 * @param Vector3               $place
	 * @param string                $task
	 * @param AdditionalDataManager $data
	 * @param Vector3               $splitOffset
	 */
	public function __construct(Selection $selection, Pattern $pattern, string $world, Vector3 $place, string $task, AdditionalDataManager $data, Vector3 $splitOffset)
	{
		$this->selection = $selection;
		$this->pattern = $pattern;
		$this->world = $world;
		$this->place = $place->floor();
		$this->task = $task;
		$this->data = $data;
		$this->splitOffset = $splitOffset->floor();
	}

	/**
	 * @return Selection
	 */
	public function getSelection(): Selection
	{
		return $this->selection;
	}

	/**
	 * @return Pattern
	 */
	public function getPattern(): Pattern
	{
		return $this->pattern;
	}

	/**
	 * @return Vector3
	 */
	public function getPlace(): Vector3
	{
		return $this->place;
	}

	/**
	 * @return string
	 */
	public function getTask(): string
	{
		return $this->task;
	}

	/**
	 * @return AdditionalDataManager
	 */
	public function getData(): AdditionalDataManager
	{
		return $this->data;
	}

	/**
	 * @return bool
	 */
	public function isInstant(): bool
	{
		return false;
	}

	public function execute(): void
	{
		if ($this->selection instanceof LinkedBlockListSelection) {
			$temp = $this->selection;
			$this->selection = $temp->get();
			if ($this->task === UndoTask::class || $this->task === RedoTask::class) {
				$temp->clear();
			}
			$this->world = $this->selection->getWorldName();
		}
		$this->pieces = $this->selection->split($this->getSplitOffset());
		$this->totalLength = count($this->pieces);

		if (count($this->pieces) === 1) {
			$this->getData()->setFinal();
		}
		$this->startPiece($this->getData());
	}

	/**
	 * @return bool whether all pieces are done
	 */
	public function continue(): bool
	{
		if ($this->currentPiece->isFinished()) {
			$result = $this->currentPiece->getResult();
			$data = $this->currentPiece->getAdditionalData();

			if ($result instanceof EditTaskResult && $data instanceof AdditionalDataManager) {
				if ($data->isSavingChunks()) {
					ResultingChunkData::from($result->getManager()->getWorldName(), $result->getManager()->getChunks(), $result->getTiles());
				}

				if (isset($this->result)) {
					$this->result->merge($result);
				} else {
					$this->result = $result;
				}

				$result->free();

				if (count($this->pieces) > 0) {
					$data->donePiece();
					if (count($this->pieces) === 1) {
						$data->setFinal();
					}

					$this->startPiece($data);
					return false; //more to go
				}

				TaskResultData::from($this->selection->getPlayer(), $this->getTask(), $this->result->getTime(), $this->result->getChanged(), $data, $this->currentPiece->getChangeId());
			}
			return true;
		}
		return false; //not finished yet
	}

	/**
	 * @param AdditionalDataManager $data
	 */
	private function startPiece(AdditionalDataManager $data): void
	{
		$piece = array_pop($this->pieces);
		if (!$piece instanceof Selection) {
			throw new UnexpectedValueException("Tried to start executing without any pieces in stack");
		}

		$task = $this->getTask();
		$this->currentPiece = new $task($piece, $this->getPattern(), $this->world, $this->getPlace(), $data, $data->isFirstPiece() ? $this->selection : null);
	}

	/**
	 * @return Vector3
	 */
	public function getSplitOffset(): Vector3
	{
		return $this->splitOffset;
	}

	/**
	 * @return int
	 */
	public function getTotalLength(): int
	{
		return $this->totalLength;
	}

	/**
	 * @return int
	 */
	public function getLength(): int
	{
		return count($this->pieces) + 1;
	}

	/**
	 * @return EditTaskResult|null Current result of task, may not be finished yet
	 */
	public function getResult(): ?EditTaskResult
	{
		return $this->result ?? null;
	}

	/**
	 * @return string
	 */
	public function fastSerialize(): string
	{
		$stream = new ExtendedBinaryStream();

		$stream->putString($this->selection->fastSerialize());
		$stream->putString($this->pattern->fastSerialize());
		$stream->putString($this->world);
		$stream->putVector($this->place);
		$stream->putString($this->task);
		$stream->putString(igbinary_serialize($this->data));
		$stream->putVector($this->splitOffset);

		return $stream->getBuffer();
	}

	/**
	 * @param string $data
	 * @return QueuedEditTask
	 */
	public static function fastDeserialize(string $data): QueuedEditTask
	{
		$stream = new ExtendedBinaryStream($data);

		return new QueuedEditTask(Selection::fastDeserialize($stream->getString()), Pattern::fastDeserialize($stream->getString()), $stream->getString(), $stream->getVector(), $stream->getString(), igbinary_unserialize($stream->getString()), $stream->getVector());
	}

	/**
	 * @return EditTask
	 */
	public function getCurrentPiece(): EditTask
	{
		return $this->currentPiece;
	}
}