<?php

namespace platz1de\EasyEdit\command\defaults\clipboard;

use Generator;
use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\exception\InvalidUsageException;
use platz1de\EasyEdit\command\flags\CommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\flags\StringCommandFlag;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\schematic\SchematicSaveTask;
use platz1de\EasyEdit\utils\ArgumentParser;

class SaveSchematicCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/saveschematic", [KnownPermissions::PERMISSION_WRITEDISK, KnownPermissions::PERMISSION_CLIPBOARD], ["/save"]);
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		$schematicName = pathinfo($flags->getStringFlag("schematic"), PATHINFO_FILENAME);
		if ($schematicName === "") {
			throw new InvalidUsageException($this);
		}

		$session->runTask(new SchematicSaveTask($session->getClipboard(), $schematicName));
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
		ArgumentParser::requireArgumentCount($args, 1, $this);
		yield StringCommandFlag::with($args[0] ?? "", "schematic");
	}
}