<?php

namespace platz1de\EasyEdit\command\defaults;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionManager;
use platz1de\EasyEdit\selection\Sphere;
use platz1de\EasyEdit\task\selection\CountTask;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;
use pocketmine\player\Player;
use Throwable;

class CountCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/count", "Count selected blocks", "easyedit.command.count");
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		if (isset($args[0])) {
			$selection = Sphere::aroundPoint($player->getName(), $player->getWorld()->getFolderName(), $player->getPosition(), (int) $args[0]);
		} else {
			try {
				$selection = SelectionManager::getFromPlayer($player->getName());
				Selection::validate($selection);
			} catch (Throwable) {
				Messages::send($player, "no-selection");
				return;
			}
		}

		CountTask::queue($selection, $player->getPosition());
	}

	/**
	 * @return CommandParameter[][]
	 */
	public function getCommandOverloads(): array
	{
		return [
			[
				CommandParameter::standard("radius", AvailableCommandsPacket::ARG_TYPE_FLOAT, 0, true)
			]
		];
	}
}