<?php

namespace platz1de\EasyEdit\task;

use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\worker\WorkerAdapter;

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
			if ($result instanceof ReferencedChunkManager) {
				foreach ($result->getChunks() as $chunk) {
					$result->getLevel()->setChunk($chunk->getX(), $chunk->getZ(), $chunk, false);
				}
			}
			if (count($this->pieces) > 0) {
				$task = $this->task->getTask();
				$this->currentPiece = new $task(array_pop($this->pieces), $this->task->getPattern(), $this->task->getPlace());
				EasyEdit::getWorker()->stack($this->currentPiece);
			} else {
				return true;
			}
		}
		return false;
	}

	public function start(): void
	{
		$task = $this->task->getTask();
		$this->currentPiece = new $task(array_pop($this->pieces), $this->task->getPattern(), $this->task->getPlace());
		EasyEdit::getWorker()->stack($this->currentPiece);
	}
}
