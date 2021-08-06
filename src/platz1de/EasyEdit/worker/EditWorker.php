<?php

namespace platz1de\EasyEdit\worker;

use pocketmine\thread\Worker;
use ThreadedLogger;

class EditWorker extends Worker
{
	public const STATUS_IDLE = 0;
	public const STATUS_PREPARING = 1;
	public const STATUS_RUNNING = 2;

	/**
	 * @var ThreadedLogger
	 */
	private $logger;
	/**
	 * @var int
	 */
	private $status = self::STATUS_IDLE;
	/**
	 * @var float currently just last start/end of a task
	 */
	private $lastResponse;

	/**
	 * EditWorker constructor.
	 * @param ThreadedLogger $logger
	 */
	public function __construct(ThreadedLogger $logger)
	{
		$this->logger = $logger;
	}


	public function onRun(): void
	{
		gc_enable();

		$this->getLogger()->debug("Started EditWorker");

		$this->lastResponse = microtime(true);
	}

	/**
	 * @return ThreadedLogger
	 */
	public function getLogger(): ThreadedLogger
	{
		return $this->logger;
	}

	/**
	 * @return string
	 */
	public function getThreadName(): string
	{
		return "EditWorker";
	}

	/**
	 * @return bool
	 */
	public function isRunning(): bool
	{
		return $this->getStatus() === self::STATUS_RUNNING;
	}

	/**
	 * @return int
	 */
	public function getStatus(): int
	{
		return $this->status;
	}

	/**
	 * @param int $status
	 */
	public function setStatus(int $status): void
	{
		$this->status = $status;
		$this->lastResponse = microtime(true);
	}

	//TODO: Implement proper callbacks

	/**
	 * @return float
	 */
	public function getLastResponse(): float
	{
		return $this->getStatus() === self::STATUS_IDLE ? microtime(true) : $this->lastResponse;
	}
}