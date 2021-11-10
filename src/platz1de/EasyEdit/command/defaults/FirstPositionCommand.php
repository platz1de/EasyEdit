<?php

namespace platz1de\EasyEdit\command\defaults;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\selection\Cube;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;
use pocketmine\player\Player;

class FirstPositionCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/pos1", "Set the first Position", "easyedit.position", ["/1"]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		if(count($args) > 2){
			Cube::selectPos1($player, new Vector3((int) $args[0], (int) $args[1], (int) $args[2]));
		}else{
			Cube::selectPos1($player, $player->getPosition()->floor());
		}
	}

	/**
	 * @return CommandParameter[][]
	 */
	public function getCommandOverloads(): array
	{
		return [
			[
				CommandParameter::standard("x", AvailableCommandsPacket::ARG_TYPE_INT),
				CommandParameter::standard("y", AvailableCommandsPacket::ARG_TYPE_INT),
				CommandParameter::standard("z", AvailableCommandsPacket::ARG_TYPE_INT),
			],
			[]
		];
	}
}