<?php

namespace platz1de\EasyEdit\task;

use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\selection\Selection;
use pocketmine\tile\Tile;

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
				foreach ($result->getManager()->getChunks() as $chunk) {
					$c = $result->getManager()->getLevel()->getChunk($chunk->getX(), $chunk->getZ());
					if ($c !== null) {
						foreach ($c->getTiles() as $tile) {
							$tile->close();
						}
					}

					$result->getManager()->getLevel()->setChunk($chunk->getX(), $chunk->getZ(), $chunk, false);
				}

				foreach ($result->getTiles() as $tile) {
					Tile::createTile($tile->getString(Tile::TAG_ID), $result->getManager()->getLevel(), $tile);
				}


				if (count($this->pieces) > 0) {
					$task = $this->task->getTask();
					$this->currentPiece = new $task(array_pop($this->pieces), $this->task->getPattern(), $this->task->getPlace(), $result);
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
		$this->currentPiece = new $task(array_pop($this->pieces), $this->task->getPattern(), $this->task->getPlace(), $this->task->getSelection());
		EasyEdit::getWorker()->stack($this->currentPiece);
	}
}
