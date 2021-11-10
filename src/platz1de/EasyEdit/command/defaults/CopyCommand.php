<?php

namespace platz1de\EasyEdit\command\defaults;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\selection\Cube;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionManager;
use platz1de\EasyEdit\task\selection\CopyTask;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;
use pocketmine\player\Player;
use Throwable;

class CopyCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/copy", "Copy the selected Area", "easyedit.command.copy");
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		try {
			$selection = SelectionManager::getFromPlayer($player->getName());
			/** @var Cube $selection */
			Selection::validate($selection, Cube::class);
		} catch (Throwable) {
			Messages::send($player, "no-selection");
			return;
		}

		CopyTask::queue($selection, $player->getPosition());
	}

	/**
	 * @return CommandParameter[][]
	 */
	public function getCommandOverloads(): array
	{
		return [];
	}
}