<?php

namespace platz1de\EasyEdit\task;

use platz1de\EasyEdit\selection\LinkedBlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\task\queued\QueuedEditTask;
use platz1de\EasyEdit\task\selection\RedoTask;
use platz1de\EasyEdit\task\selection\UndoTask;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\thread\output\ResultingChunkData;
use platz1de\EasyEdit\thread\output\TaskResultData;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use UnexpectedValueException;

class PieceManager
{
	private QueuedEditTask $task;
	/**
	 * @var Selection[]
	 */
	private array $pieces;
	private EditTask $currentPiece;
	private int $totalLength;
	private EditTaskResult $result;
	private Selection $selection;
	private string $world;

	/**
	 * PieceManager constructor.
	 * @param QueuedEditTask $task
	 */
	public function __construct(QueuedEditTask $task)
	{
		$this->task = $task;
		$sel = $task->getSelection();
		$this->selection = $sel instanceof LinkedBlockListSelection ? $sel->get() : $sel;
		if ($sel instanceof LinkedBlockListSelection && ($this->task->getTask() === UndoTask::class || $this->task->getTask() === RedoTask::class)) {
			$sel->clear();
		}
		$this->world = $sel instanceof LinkedBlockListSelection ? $this->selection->getWorldName() : $this->task->getWorldName();
		$this->pieces = $this->selection->split($task->getSplitOffset());
		$this->totalLength = count($this->pieces);
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
					EditThread::getInstance()->sendOutput(ResultingChunkData::from($result->getManager()->getWorldName(), $result->getManager()->getChunks(), $result->getTiles()));
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

				EditThread::getInstance()->sendOutput(TaskResultData::from($this->selection->getPlayer(), $this->task->getTask(), $this->result->getTime(), $this->result->getChanged(), $data, $this->currentPiece->getChangeId()));
			}
			return true;
		}
		return false; //not finished yet
	}

	public function start(): void
	{
		if (count($this->pieces) === 1) {
			$this->task->getData()->setFinal();
		}
		$this->startPiece($this->task->getData());
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

		$task = $this->task->getTask();
		$this->currentPiece = new $task($piece, $this->task->getPattern(), $this->world, $this->task->getPlace(), $data, $data->isFirstPiece() ? $this->selection : null);
	}

	/**
	 * @return EditTask
	 */
	public function getCurrent(): EditTask
	{
		return $this->currentPiece;
	}

	/**
	 * @return QueuedEditTask
	 */
	public function getQueued(): QueuedEditTask
	{
		return $this->task;
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
}