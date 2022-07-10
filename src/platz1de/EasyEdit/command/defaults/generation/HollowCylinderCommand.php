<?php

namespace platz1de\EasyEdit\command\defaults\generation;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\exception\PatternParseException;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\pattern\logic\selection\SidesPattern;
use platz1de\EasyEdit\pattern\parser\ParseError;
use platz1de\EasyEdit\pattern\parser\PatternParser;
use platz1de\EasyEdit\selection\Cylinder;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\editing\selection\pattern\SetTask;
use platz1de\EasyEdit\utils\ArgumentParser;

class HollowCylinderCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/hcylinder", [KnownPermissions::PERMISSION_GENERATE, KnownPermissions::PERMISSION_EDIT], ["/hcy", "/hollowcylinder"]);
	}

	/**
	 * @param Session  $session
	 * @param string[] $args
	 */
	public function process(Session $session, array $args): void
	{
		ArgumentParser::requireArgumentCount($args, 3, $this);
		try {
			$pattern = new SidesPattern((float) ($args[3] ?? 1.0), [PatternParser::parseInput($args[2], $session->asPlayer())]);
		} catch (ParseError $exception) {
			throw new PatternParseException($exception);
		}

		$session->runTask(SetTask::from($session->asPlayer()->getWorld()->getFolderName(), null, Cylinder::aroundPoint($session->asPlayer()->getWorld()->getFolderName(), $session->asPlayer()->getPosition(), (float) $args[0], (int) $args[1]), $session->asPlayer()->getPosition(), $pattern));
	}
}