<?php

namespace platz1de\EasyEdit\command\defaults\clipboard;

use platz1de\EasyEdit\command\EasyEditCommand;
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
	 * @param Session  $session
	 * @param string[] $args
	 */
	public function process(Session $session, array $args): void
	{
		$schematicName = pathinfo($args[0] ?? "", PATHINFO_FILENAME);
		if (!isset($args[0]) || !SchematicFileAdapter::schematicExists(EasyEdit::getSchematicPath() . $schematicName)) {
			$session->sendMessage("unknown-schematic", ["{schematic}" => $schematicName, "{known}" => implode(", ", SchematicFileAdapter::getSchematicList())]);
			return;
		}

		$session->runTask(new SchematicLoadTask(EasyEdit::getSchematicPath() . $schematicName));
	}
}