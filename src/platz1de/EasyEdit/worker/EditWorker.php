<?php

namespace platz1de\EasyEdit\worker;

use pocketmine\block\BlockFactory;
use pocketmine\item\ItemFactory;
use pocketmine\world\biome\Biome;
use pocketmine\utils\Utils;
use pocketmine\Worker;
use ThreadedLogger;

class EditWorker extends Worker
{
	/**
	 * @var ThreadedLogger
	 */
	private $logger;
	/**
	 * @var bool
	 */
	private $running = false;
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


	public function run(): void
	{
		error_reporting(-1);

		$this->registerClassLoader();

		set_error_handler([Utils::class, 'errorExceptionHandler']);

		gc_enable();

		$this->getLogger()->debug("Started EditWorker");

		Biome::init();
		BlockFactory::init();
		ItemFactory::init();

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
		return $this->running;
	}

	/**
	 * @param bool $running
	 */
	public function setRunning(bool $running = true): void
	{
		$this->running = $running;
		$this->lastResponse = microtime(true);
	}

	//TODO: Implement proper callbacks
	/**
	 * @return float
	 */
	public function getLastResponse(): float
	{
		return $this->isRunning() ? $this->lastResponse : microtime(true);
	}
}