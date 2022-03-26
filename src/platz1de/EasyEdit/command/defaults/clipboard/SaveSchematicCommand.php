<?php

namespace platz1de\EasyEdit\command\defaults\clipboard;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\exception\InvalidUsageException;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\task\schematic\SchematicSaveTask;
use platz1de\EasyEdit\utils\ArgumentParser;
use pocketmine\player\Player;

class SaveSchematicCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/saveschematic", [KnownPermissions::PERMISSION_WRITEDISK, KnownPermissions::PERMISSION_CLIPBOARD], ["/save"]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		ArgumentParser::requireArgumentCount($args, 1, $this);
		$schematicName = pathinfo($args[0] ?? "", PATHINFO_FILENAME);
		if ($schematicName === "") {
			throw new InvalidUsageException($this);
		}

		SchematicSaveTask::queue($player->getName(), ArgumentParser::getClipboard($player), $schematicName);
	}
}