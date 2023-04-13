<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use Generator;
use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\flags\CommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\flags\VectorCommandFlag;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\math\OffGridBlockVector;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\editing\selection\move\MoveTask;
use platz1de\EasyEdit\utils\ArgumentParser;

class MoveCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/move", [KnownPermissions::PERMISSION_EDIT]);
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		$session->runSettingTask(new MoveTask($session->getSelection(), $flags->getVectorFlag("vector")->diff(OffGridBlockVector::zero())));
	}

	/**
	 * @param Session $session
	 * @return CommandFlag[]
	 */
	public function getKnownFlags(Session $session): array
	{
		return [
			"vector" => new VectorCommandFlag("vector", [], "v")
		];
	}

	/**
	 * @param CommandFlagCollection $flags
	 * @param Session               $session
	 * @param string[]              $args
	 * @return Generator<CommandFlag>
	 */
	public function parseArguments(CommandFlagCollection $flags, Session $session, array $args): Generator
	{
		if (!$flags->hasFlag("vector")) {
			yield VectorCommandFlag::with(ArgumentParser::parseDirectionVector($session, $args[0] ?? null, $args[1] ?? null), "vector");
		}
	}
}