<?php

namespace platz1de\EasyEdit\command\defaults\clipboard;

use platz1de\EasyEdit\command\flags\CommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\flags\SingularCommandFlag;
use platz1de\EasyEdit\command\flags\VectorCommandFlag;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\command\SimpleFlagArgumentCommand;
use platz1de\EasyEdit\math\OffGridBlockVector;
use platz1de\EasyEdit\result\CuttingTaskResult;
use platz1de\EasyEdit\result\EditTaskResult;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\editing\CopyTask;
use platz1de\EasyEdit\task\editing\CutTask;
use platz1de\EasyEdit\utils\MixedUtils;
use RuntimeException;

class CopyCommand extends SimpleFlagArgumentCommand
{
	public function __construct()
	{
		parent::__construct("/copy", [], [KnownPermissions::PERMISSION_CLIPBOARD]);
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		if ($flags->hasFlag("remove")) {
			if (!$this->testPermission($session->asPlayer(), KnownPermissions::PERMISSION_EDIT)) {
				return;
			}
			$session->runTask(new CutTask($session->getSelection(), $flags->getVectorFlag("relative")))->then(function (EditTaskResult $result) use ($session) {
				if (!$result instanceof CuttingTaskResult) {
					throw new RuntimeException("Expected CuttingTaskResult");
				}
				$session->sendMessage("blocks-cut", ["{time}" => (string) round($result->getTime(), 2), "{changed}" => MixedUtils::humanReadable($result->getAffected())]);
				$session->addToHistory($result->getSelection(), false);
				$session->setClipboard($result->getClipboard());
			});
		} else {
			$session->runTask(new CopyTask($session->getSelection(), $flags->getVectorFlag("relative")))->then(function (EditTaskResult $result) use ($session) {
				$session->sendMessage("blocks-copied", ["{time}" => (string) round($result->getTime(), 2), "{changed}" => MixedUtils::humanReadable($result->getAffected())]);
				$session->setClipboard($result->getSelection());
			});
		}
	}

	/**
	 * @param Session $session
	 * @return CommandFlag[]
	 */
	public function getKnownFlags(Session $session): array
	{
		return [
			"center" => VectorCommandFlag::with($session->getSelection()->getBottomCenter()->offGrid(), "relative", [], "c"),
			"position" => VectorCommandFlag::default(OffGridBlockVector::fromVector($session->asPlayer()->getPosition()), "relative", [], "p"),
			"remove" => new SingularCommandFlag("remove", [], "r")
		];
	}
}