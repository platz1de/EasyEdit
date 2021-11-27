<?php

namespace platz1de\EasyEdit\command\defaults\clipboard;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\selection\ClipBoardManager;
use platz1de\EasyEdit\task\schematic\SchematicSaveTask;
use pocketmine\player\Player;
use Throwable;

class SaveSchematicCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/saveschematic", "Save your clipboard into a schematic", [KnownPermissions::PERMISSION_WRITEDISK, KnownPermissions::PERMISSION_CLIPBOARD], "//saveschematic <schematicName>", ["/save"]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		$schematicName = pathinfo($args[0] ?? "", PATHINFO_FILENAME);
		if ($schematicName === "") {
			$player->sendMessage($this->getUsage());
			return;
		}

		try {
			$selection = ClipBoardManager::getFromPlayer($player->getName());
		} catch (Throwable) {
			Messages::send($player, "no-clipboard");
			return;
		}

		SchematicSaveTask::queue($player->getName(), $selection, $schematicName);
	}
}