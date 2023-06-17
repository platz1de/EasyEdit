<?php

namespace platz1de\EasyEdit\command\defaults\brush;

use Generator;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\command\OverloadedCommand;
use platz1de\EasyEdit\session\Session;
use RuntimeException;

class BrushCommand extends OverloadedCommand
{
	public function __construct()
	{
		parent::__construct("/brush", [
			new SphericalBrushSubCommand(),
			new SmoothingBrushSubCommand(),
			new NaturalizingBrushSubCommand(),
			new CylindricalBrushSubCommand(),
			new PastingBrushSubCommand()
		], true, [KnownPermissions::PERMISSION_BRUSH], ["/br"]);
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		throw new RuntimeException("This method should not be called");
	}

	public function getKnownFlags(Session $session): array
	{
		throw new RuntimeException("This method should not be called");
	}

	public function parseArguments(CommandFlagCollection $flags, Session $session, array $args): Generator
	{
		throw new RuntimeException("This method should not be called");
	}
}