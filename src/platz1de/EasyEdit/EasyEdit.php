<?php

namespace platz1de\EasyEdit;

use platz1de\EasyEdit\command\CommandManager;
use platz1de\EasyEdit\command\defaults\BrushCommand;
use platz1de\EasyEdit\command\defaults\CenterCommand;
use platz1de\EasyEdit\command\defaults\CopyCommand;
use platz1de\EasyEdit\command\defaults\CountCommand;
use platz1de\EasyEdit\command\defaults\CylinderCommand;
use platz1de\EasyEdit\command\defaults\ExtendCommand;
use platz1de\EasyEdit\command\defaults\FirstPositionCommand;
use platz1de\EasyEdit\command\defaults\HollowCylinderCommand;
use platz1de\EasyEdit\command\defaults\HollowSphereCommand;
use platz1de\EasyEdit\command\defaults\InsertCommand;
use platz1de\EasyEdit\command\defaults\MoveCommand;
use platz1de\EasyEdit\command\defaults\NaturalizeCommand;
use platz1de\EasyEdit\command\defaults\PasteCommand;
use platz1de\EasyEdit\command\defaults\RedoCommand;
use platz1de\EasyEdit\command\defaults\ReplaceCommand;
use platz1de\EasyEdit\command\defaults\SecondPositionCommand;
use platz1de\EasyEdit\command\defaults\SetCommand;
use platz1de\EasyEdit\command\defaults\SidesCommand;
use platz1de\EasyEdit\command\defaults\SmoothCommand;
use platz1de\EasyEdit\command\defaults\SphereCommand;
use platz1de\EasyEdit\command\defaults\StackCommand;
use platz1de\EasyEdit\command\defaults\StatusCommand;
use platz1de\EasyEdit\command\defaults\UndoCommand;
use platz1de\EasyEdit\command\defaults\WallCommand;
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

	public function onEnable(): void
	{
		self::$instance = $this;

		Messages::load();

		self::$worker = new EditWorker(Server::getInstance()->getLogger());
		self::$worker->start(PTHREADS_INHERIT_INI | PTHREADS_INHERIT_CONSTANTS);

		$this->getScheduler()->scheduleRepeatingTask(new WorkerAdapter(), 1);

		Server::getInstance()->getPluginManager()->registerEvents(new EventListener(), $this);

		CommandManager::registerCommands([
			new SetCommand(),
			new FirstPositionCommand(),
			new SecondPositionCommand(),
			new UndoCommand(),
			new RedoCommand(),
			new CopyCommand(),
			new PasteCommand(),
			new ReplaceCommand(),
			new InsertCommand(),
			new CenterCommand(),
			new ExtendCommand(),
			new MoveCommand(),
			new SphereCommand(),
			new HollowSphereCommand(),
			new StackCommand(),
			new BrushCommand(),
			new NaturalizeCommand(),
			new SmoothCommand(),
			new CylinderCommand(),
			new HollowCylinderCommand(),
			new WallCommand(),
			new SidesCommand(),
			new CountCommand(),
			new StatusCommand()
		]);
	}

	/**
	 * @return EasyEdit
	 */
	public static function getInstance(): self
	{
		return self::$instance;
	}

	/**
	 * @return EditWorker
	 */
	public static function getWorker(): EditWorker
	{
		return self::$worker;
	}
}