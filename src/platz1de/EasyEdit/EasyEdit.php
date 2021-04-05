<?php

namespace platz1de\EasyEdit;

use Exception;
use platz1de\EasyEdit\command\CommandManager;
use platz1de\EasyEdit\command\defaults\BrushCommand;
use platz1de\EasyEdit\command\defaults\CenterCommand;
use platz1de\EasyEdit\command\defaults\CopyCommand;
use platz1de\EasyEdit\command\defaults\ExtendCommand;
use platz1de\EasyEdit\command\defaults\FirstPositionCommand;
use platz1de\EasyEdit\command\defaults\HollowSphereCommand;
use platz1de\EasyEdit\command\defaults\InsertCommand;
use platz1de\EasyEdit\command\defaults\MoveCommand;
use platz1de\EasyEdit\command\defaults\NaturalizeCommand;
use platz1de\EasyEdit\command\defaults\PasteCommand;
use platz1de\EasyEdit\command\defaults\RedoCommand;
use platz1de\EasyEdit\command\defaults\ReplaceCommand;
use platz1de\EasyEdit\command\defaults\SecondPositionCommand;
use platz1de\EasyEdit\command\defaults\SetCommand;
use platz1de\EasyEdit\command\defaults\SmoothCommand;
use platz1de\EasyEdit\command\defaults\SphereCommand;
use platz1de\EasyEdit\command\defaults\StackCommand;
use platz1de\EasyEdit\command\defaults\UndoCommand;
use platz1de\EasyEdit\selection\Cube;
use platz1de\EasyEdit\selection\SelectionManager;
use platz1de\EasyEdit\worker\EditWorker;
use platz1de\EasyEdit\worker\WorkerAdapter;
use pocketmine\math\Vector3;
use pocketmine\Player;
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
			new SmoothCommand()
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

	/**
	 * @param Player  $player
	 * @param Vector3 $position
	 */
	public static function selectPos1(Player $player, Vector3 $position): void
	{
		try {
			$selection = SelectionManager::getFromPlayer($player->getName());
			if (!$selection instanceof Cube) {
				$selection->close();
				$selection = new Cube($player->getName(), $player->getLevelNonNull()->getName());
			}
		} catch (Exception $exception) {
			$selection = new Cube($player->getName(), $player->getLevelNonNull()->getName());
		}

		$selection->setPos1($position->floor());

		SelectionManager::setForPlayer($player->getName(), $selection);

		Messages::send($player, "selected-pos1");
	}

	/**
	 * @param Player  $player
	 * @param Vector3 $position
	 */
	public static function selectPos2(Player $player, Vector3 $position): void
	{
		try {
			$selection = SelectionManager::getFromPlayer($player->getName());
			if (!$selection instanceof Cube) {
				$selection->close();
				$selection = new Cube($player->getName(), $player->getLevelNonNull()->getName());
			}
		} catch (Exception $exception) {
			$selection = new Cube($player->getName(), $player->getLevelNonNull()->getName());
		}

		$selection->setPos2($position->floor());

		SelectionManager::setForPlayer($player->getName(), $selection);

		Messages::send($player, "selected-pos2");
	}
}