<?php

namespace platz1de\EasyEdit\command\defaults\clipboard;

use Generator;
use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\flags\CommandArgumentFlag;
use platz1de\EasyEdit\command\flags\CommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\schematic\SchematicFileAdapter;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\schematic\SchematicLoadTask;

class LoadSchematicCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/loadschematic", [KnownPermissions::PERMISSION_READDISK, KnownPermissions::PERMISSION_CLIPBOARD], ["/load"]);
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		$schematicName = $flags->hasFlag("schematic") ? pathinfo($flags->getStringFlag("schematic"), PATHINFO_FILENAME) : "";
		if ($schematicName === "" || !SchematicFileAdapter::schematicExists(EasyEdit::getSchematicPath() . $schematicName)) {
			$session->sendMessage("unknown-schematic", ["{schematic}" => $schematicName, "{known}" => implode(", ", SchematicFileAdapter::getSchematicList())]);
			return;
		}

		$session->runTask(new SchematicLoadTask(EasyEdit::getSchematicPath() . $schematicName));
	}

	/**
	 * @param Session $session
	 * @return CommandFlag[]
	 */
	public function getKnownFlags(Session $session): array
	{
		return [];
	}

	/**
	 * @param CommandFlagCollection $flags
	 * @param Session               $session
	 * @param string[]              $args
	 * @return Generator<CommandFlag>
	 */
	public function parseArguments(CommandFlagCollection $flags, Session $session, array $args): Generator
	{
		if(isset($args[0])){
			yield new CommandArgumentFlag("schematic", $args[0]);
		}
	}
}