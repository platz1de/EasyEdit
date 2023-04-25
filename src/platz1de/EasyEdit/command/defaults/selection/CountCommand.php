<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use InvalidArgumentException;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\result\CountingTaskResult;
use platz1de\EasyEdit\result\EditTaskResult;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\editing\CountTask;
use platz1de\EasyEdit\utils\BlockParser;
use platz1de\EasyEdit\utils\MixedUtils;

class CountCommand extends SphericalSelectionCommand
{
	public function __construct()
	{
		parent::__construct("/count", [KnownPermissions::PERMISSION_SELECT]);
	}

	/**
	 * @param Session   $session
	 * @param Selection $selection
	 */
	public function processSelection(Session $session, Selection $selection): void
	{
		$session->runTask(new CountTask($selection))->then(function (EditTaskResult $result) use ($session): void {
			if (!$result instanceof CountingTaskResult) { //TODO: Remove this once the stupid EditTask inheritance is gone
				throw new InvalidArgumentException("Expected CountingTaskResult, got " . get_class($result));
			}
			$blocks = [];
			foreach ($result->getBlocks() as $block => $count) {
				$blocks[] = BlockParser::runtimeToStateString($block) . ": " . MixedUtils::humanReadable($count);
			}
			$session->sendMessage("blocks-counted", ["{time}" => $result->getFormattedTime(), "{changed}" => MixedUtils::humanReadable($result->getAffected()), "{blocks}" => implode("\n", $blocks)]);
		});
	}
}