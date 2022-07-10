<?php

namespace platz1de\EasyEdit\command\defaults\clipboard;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\exception\InvalidUsageException;
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
	 * @param Session  $session
	 * @param string[] $args
	 */
	public function process(Session $session, array $args): void
	{
		ArgumentParser::requireArgumentCount($args, 1, $this);
		$schematicName = pathinfo($args[0] ?? "", PATHINFO_FILENAME);
		if ($schematicName === "") {
			throw new InvalidUsageException($this);
		}

		SchematicSaveTask::queue($session->getIdentifier(), $session->getClipboard(), $schematicName);
	}
}