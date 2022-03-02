<?php

namespace platz1de\EasyEdit\utils;

use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\schematic\BlockConvertor;
use pocketmine\block\Block;

class BlockInfoTool
{
	/**
	 * @param string $player
	 * @param Block  $block
	 */
	public static function display(string $player, Block $block): void
	{
		Messages::send($player, "block-info", [
			"{id}" => (string) $block->getId(),
			"{meta}" => (string) $block->getMeta(),
			"{name}" => $block->getName(),
			"{x}" => (string) $block->getPosition()->getX(),
			"{y}" => (string) $block->getPosition()->getY(),
			"{z}" => (string) $block->getPosition()->getZ(),
			"{java_state}" => BlockConvertor::getState($block->getId(), $block->getMeta())
		]);
	}
}