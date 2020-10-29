<?php

namespace platz1de\EasyEdit;

use platz1de\EasyEdit\worker\EditWorker;
use platz1de\EasyEdit\worker\WorkerAdapter;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;

class EasyEdit extends PluginBase
{
	/**
	 * @var EasyEdit
	 */
	private static $instance;
	/**
	 * @var EditWorker
	 */
	private static $worker;

	public function onEnable()
	{
		self::$instance = $this;

		self::$worker = new EditWorker(Server::getInstance()->getLogger());
		self::$worker->start();

		$this->getScheduler()->scheduleRepeatingTask(new WorkerAdapter(), 1);
	}

	/**
	 * @return EasyEdit
	 */
	public static function getInstance()
	{
		return self::$instance;
	}

	/**
	 * @return EditWorker
	 */
	public static function getWorker()
	{
		return self::$worker;
	}
}