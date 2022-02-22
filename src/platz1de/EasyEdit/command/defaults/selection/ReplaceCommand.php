<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\exception\PatternParseException;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\pattern\logic\relation\BlockPattern;
use platz1de\EasyEdit\pattern\parser\ParseError;
use platz1de\EasyEdit\pattern\parser\PatternParser;
use platz1de\EasyEdit\pattern\PatternArgumentData;
use platz1de\EasyEdit\task\editing\selection\pattern\SetTask;
use platz1de\EasyEdit\utils\ArgumentParser;
use pocketmine\player\Player;

class ReplaceCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/replace", "Replace the selected Area", [KnownPermissions::PERMISSION_EDIT], "//replace <block> <pattern>");
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		if (($args[1] ?? "") === "") {
			$player->sendMessage($this->getUsage());
			return;
		}

		try {
			$block = PatternParser::getBlockType($args[0]);
		} catch (ParseError $exception) {
			throw new PatternParseException($exception);
		}

		SetTask::queue(ArgumentParser::getSelection($player), BlockPattern::from([ArgumentParser::parseCombinedPattern($player, $args, 1)], PatternArgumentData::create()->setBlock($block)), $player->getPosition());
	}
}