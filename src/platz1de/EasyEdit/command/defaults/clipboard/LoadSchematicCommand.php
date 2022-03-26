<?php

namespace platz1de\EasyEdit\command\defaults\clipboard;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\schematic\SchematicFileAdapter;
use platz1de\EasyEdit\task\schematic\SchematicLoadTask;
use pocketmine\player\Player;

class LoadSchematicCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/loadschematic", [KnownPermissions::PERMISSION_READDISK, KnownPermissions::PERMISSION_CLIPBOARD], ["/load"]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		$schematicName = pathinfo($args[0] ?? "", PATHINFO_FILENAME);
		if (!isset($args[0]) || !SchematicFileAdapter::schematicExists(EasyEdit::getSchematicPath() . $schematicName)) {
			Messages::send($player, "unknown-schematic", ["{schematic}" => $schematicName, "{known}" => implode(", ", SchematicFileAdapter::getSchematicList())]);
			return;
		}

		SchematicLoadTask::queue($player->getName(), $schematicName);
	}
}