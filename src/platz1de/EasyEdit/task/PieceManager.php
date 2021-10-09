<?php

namespace platz1de\EasyEdit\task;

use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\task\queued\QueuedCallbackTask;
use platz1de\EasyEdit\task\queued\QueuedEditTask;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\LoaderManager;
use platz1de\EasyEdit\utils\MixedUtils;
use platz1de\EasyEdit\worker\WorkerAdapter;
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

	/**
	 * PieceManager constructor.
	 * @param QueuedEditTask $task
	 */
	public function __construct(QueuedEditTask $task)
	{
		$this->task = $task;
		$this->pieces = $task->getSelection()->split($task->getSplitOffset());
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
					LoaderManager::setChunks($result->getManager()->getWorld(), $result->getManager()->getChunks(), $result->getTiles());
				}

				if (isset($this->result)) {
					$this->result->merge($result);
					$result->getUndo()->free();
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

				$this->task->finishWith($this->result);

				$this->currentPiece->notifyUser($this->task->getSelection(), round($this->result->getTime(), 2), MixedUtils::humanReadable($this->result->getChanged()), $data);
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
		$this->currentPiece = new $task($piece, $this->task->getPattern(), $this->task->getPlace(), $data, $data->isFirstPiece() ? $this->task->getSelection() : null);
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