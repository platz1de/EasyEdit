<?php

namespace platz1de\EasyEdit\worker;

use pocketmine\block\BlockFactory;
use pocketmine\level\biome\Biome;
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
}