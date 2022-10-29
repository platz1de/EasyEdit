<?php

namespace platz1de\EasyEdit\command\defaults\generation;

use platz1de\EasyEdit\command\flags\CommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\flags\FloatCommandFlag;
use platz1de\EasyEdit\command\flags\FloatValueCommandFlag;
use platz1de\EasyEdit\command\flags\PatternCommandFlag;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\command\SimpleFlagArgumentCommand;
use platz1de\EasyEdit\pattern\logic\selection\SidesPattern;
use platz1de\EasyEdit\selection\Sphere;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\editing\selection\pattern\SetTask;

class HollowSphereCommand extends SimpleFlagArgumentCommand
{
	public function __construct()
	{
		parent::__construct("/hsphere", ["radius" => true, "pattern" => true, "thickness" => false], [KnownPermissions::PERMISSION_GENERATE, KnownPermissions::PERMISSION_EDIT], ["/hsph", "/hollowsphere"]);
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		if (!$flags->hasFlag("thickness")) {
			$flags->addFlag(new FloatValueCommandFlag("thickness", 1.0));
		}

		$session->runTask(new SetTask(Sphere::aroundPoint($session->asPlayer()->getWorld()->getFolderName(), $session->asPlayer()->getPosition(), $flags->getFloatFlag("radius")), new SidesPattern($flags->getFloatFlag("thickness"), [$flags->getPatternFlag("pattern")])));
	}

	/**
	 * @param Session $session
	 * @return CommandFlag[]
	 */
	public function getKnownFlags(Session $session): array
	{
		return [
			"radius" => new FloatCommandFlag("radius", ["rad"], "r"),
			"pattern" => new PatternCommandFlag("pattern", [], "p"),
			"thickness" => new FloatCommandFlag("thickness", ["thick"], "t")
		];
	}
}