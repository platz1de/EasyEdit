<?php

namespace platz1de\EasyEdit;

use Exception;
use platz1de\EasyEdit\command\FirstPositionCommand;
use platz1de\EasyEdit\command\RedoCommand;
use platz1de\EasyEdit\command\SecondPositionCommand;
use platz1de\EasyEdit\command\SetCommand;
use platz1de\EasyEdit\command\UndoCommand;
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

	public function onEnable()
	{
		self::$instance = $this;

		Messages::load();

		self::$worker = new EditWorker(Server::getInstance()->getLogger());
		self::$worker->start();

		$this->getScheduler()->scheduleRepeatingTask(new WorkerAdapter(), 1);

		Server::getInstance()->getPluginManager()->registerEvents(new EventListener(), $this);

		Server::getInstance()->getCommandMap()->registerAll("easyedit", [
			new SetCommand(),
			new FirstPositionCommand(),
			new SecondPositionCommand(),
			new UndoCommand(),
			new RedoCommand()
		]);
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
				$selection = new Cube($player->getName(), $player->getLevel());
			}
		} catch (Exception $exception) {
			$selection = new Cube($player->getName(), $player->getLevel());
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
				$selection = new Cube($player->getName(), $player->getLevel());
			}
		} catch (Exception $exception) {
			$selection = new Cube($player->getName(), $player->getLevel());
		}

		$selection->setPos2($position->floor());

		SelectionManager::setForPlayer($player->getName(), $selection);

		Messages::send($player, "selected-pos2");
	}
}