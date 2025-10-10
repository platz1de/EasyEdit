<?php

namespace platz1de\EasyEdit\command\defaults\clipboard;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\exception\InvalidUsageException;
use platz1de\EasyEdit\command\FlagArgumentParser;
use platz1de\EasyEdit\command\flags\CommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\flags\StringCommandFlag;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\result\SelectionManipulationResult;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\schematic\SchematicSaveTask;
use platz1de\EasyEdit\utils\MixedUtils;

class SaveSchematicCommand extends EasyEditCommand
{
	use FlagArgumentParser;

	public function __construct()
	{
		parent::__construct("/saveschematic", [KnownPermissions::PERMISSION_WRITEDISK, KnownPermissions::PERMISSION_CLIPBOARD], ["/save"]);
		$this->flagOrder = ["schematic" => true];
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		$schematicName = pathinfo($flags->getStringFlag("schematic"), PATHINFO_BASENAME);
		if ($schematicName === "") {
			throw new InvalidUsageException();
		}

		$session->runTask(new SchematicSaveTask($session->getClipboard(), EasyEdit::getSchematicPath() . $schematicName))->then(function (SelectionManipulationResult $result) use ($schematicName, $session): void {
			if ($result->getChanged() === 0) {
				return;
			}
			$session->sendMessage("schematic-created", ["{time}" => $result->getFormattedTime(), "{changed}" => MixedUtils::humanReadable($result->getChanged()), "{name}" => basename($schematicName)]);
		});
	}

	/**
	 * @param Session $session
	 * @return CommandFlag[]
	 */
	public function getKnownFlags(Session $session): array
	{
		return [
			"schematic" => new StringCommandFlag("schematic", ["schem"], "s")
		];
	}
}