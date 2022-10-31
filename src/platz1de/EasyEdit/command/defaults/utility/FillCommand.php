<?php

namespace platz1de\EasyEdit\command\defaults\utility;

use platz1de\EasyEdit\command\exception\PatternParseException;
use platz1de\EasyEdit\command\flags\CommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\flags\FacingCommandFlag;
use platz1de\EasyEdit\command\flags\IntegerCommandFlag;
use platz1de\EasyEdit\command\flags\StringCommandFlag;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\command\SimpleFlagArgumentCommand;
use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\pattern\parser\ParseError;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\expanding\FillTask;
use platz1de\EasyEdit\utils\BlockParser;
use platz1de\EasyEdit\utils\VectorUtils;

class FillCommand extends SimpleFlagArgumentCommand
{
	public function __construct()
	{
		parent::__construct("/fill", ["block" => true, "direction" => false], [KnownPermissions::PERMISSION_EDIT, KnownPermissions::PERMISSION_GENERATE]);
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		if (!$flags->hasFlag("direction")) {
			$flags->addFlag(IntegerCommandFlag::with(VectorUtils::getFacing($session->asPlayer()->getLocation()), "direction"));
		}
		try {
			$block = new StaticBlock(BlockParser::parseBlockIdentifier($flags->getStringFlag("block")));
		} catch (ParseError $exception) {
			throw new PatternParseException($exception);
		}
		$session->runTask(new FillTask($session->asPlayer()->getWorld()->getFolderName(), $session->asPlayer()->getPosition()->asVector3(), $flags->getIntFlag("direction"), $block));
	}

	/**
	 * @param Session $session
	 * @return CommandFlag[]
	 */
	public function getKnownFlags(Session $session): array
	{
		return [
			"block" => new StringCommandFlag("block", [], "b"),
			"direction" => new FacingCommandFlag("direction", ["dir"], "d")
		];
	}
}