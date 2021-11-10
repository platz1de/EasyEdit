<?php

namespace platz1de\EasyEdit\command\defaults;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\pattern\functional\NaturalizePattern;
use platz1de\EasyEdit\pattern\ParseError;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\pattern\PatternParser;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionManager;
use platz1de\EasyEdit\task\selection\SetTask;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;
use pocketmine\player\Player;
use Throwable;

class NaturalizeCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/naturalize", "Naturalize the selected Area", "easyedit.command.set");
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		try {
			$top = PatternParser::parseInput($args[0] ?? "grass", $player);
			$middle = PatternParser::parseInput($args[1] ?? "dirt", $player);
			$bottom = PatternParser::parseInput($args[2] ?? "stone", $player);
		} catch (ParseError $exception) {
			$player->sendMessage($exception->getMessage());
			return;
		}

		try {
			$selection = SelectionManager::getFromPlayer($player->getName());
			Selection::validate($selection);
		} catch (Throwable) {
			Messages::send($player, "no-selection");
			return;
		}

		SetTask::queue($selection, new Pattern([new NaturalizePattern([$top, $middle, $bottom])]), $player->getPosition());
	}

	/**
	 * @return CommandParameter[][]
	 */
	public function getCommandOverloads(): array
	{
		return [
			[
				CommandParameter::standard("topBlock", AvailableCommandsPacket::ARG_TYPE_RAWTEXT),
				CommandParameter::standard("middleBlock", AvailableCommandsPacket::ARG_TYPE_RAWTEXT),
				CommandParameter::standard("bottomBlock", AvailableCommandsPacket::ARG_TYPE_RAWTEXT)
			]
		];
	}
}