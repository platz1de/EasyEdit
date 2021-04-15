<?php

namespace platz1de\EasyEdit\task;

use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\utils\LoaderManager;

class PieceManager
{
	/**
	 * @var QueuedTask
	 */
	private $task;
	/**
	 * @var Selection[]
	 */
	private $pieces;
	/**
	 * @var EditTask
	 */
	private $currentPiece;

	/**
	 * PieceManager constructor.
	 * @param QueuedTask $task
	 */
	public function __construct(QueuedTask $task)
	{
		$this->task = $task;
		$this->pieces = $task->getSelection()->split();
	}

	/**
	 * @return bool whether all pieces are done
	 */
	public function continue(): bool
	{
		if ($this->currentPiece->isFinished()) {
			$result = $this->currentPiece->getResult();

			if ($result instanceof EditTaskResult) {
				LoaderManager::setChunks($result->getManager()->getLevel(), $result->getManager()->getChunks(), $result->getTiles());

				$result->free();

				if (count($this->pieces) > 0) {
					$task = $this->task->getTask();
					$this->currentPiece = new $task(array_pop($this->pieces), $this->task->getPattern(), $this->task->getPlace(), $this->task->getData(), $result);
					EasyEdit::getWorker()->stack($this->currentPiece);
					return false; //more to go
				}

				$this->task->finishWith($result->getUndo());

				$this->currentPiece->notifyUser($this->task->getSelection(), round($result->getTime(), 3), $result->getChanged());
			}
			return true;
		}
		return false; //not finished yet
	}

	public function start(): void
	{
		$task = $this->task->getTask();
		$this->currentPiece = new $task(array_pop($this->pieces), $this->task->getPattern(), $this->task->getPlace(), $this->task->getData(), $this->task->getSelection());
		EasyEdit::getWorker()->stack($this->currentPiece);
	}
}
