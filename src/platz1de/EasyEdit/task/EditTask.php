<?php

namespace platz1de\EasyEdit\task;

use platz1de\EasyEdit\worker\EditWorker;
use Threaded;
use ThreadedLogger;
use Throwable;

abstract class EditTask extends Threaded
{
	/**
	 * @var EditWorker
	 */
	protected $worker;

	/**
	 * @var int
	 */
	private $id;

	/**
	 * @var array
	 */
	protected $chunks = [];

	public function run(): void
	{
		$this->id = $this->worker->getId();

		$this->getLogger()->debug("Running Task " . $this->getTaskName() . ":" . $this->getId());

		try {
			$this->execute();
			$this->getLogger()->debug("Task " . $this->getTaskName() . ":" . $this->getId() . " was executed successful");
		}catch (Throwable $exception){
			$this->getLogger()->logException($exception);
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

	abstract public function execute(): void;

	/**
	 * @return int
	 */
	public function getId(): int
	{
		return $this->id;
	}
}