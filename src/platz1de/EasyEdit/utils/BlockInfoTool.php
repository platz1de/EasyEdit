<?php

namespace platz1de\EasyEdit\utils;

use platz1de\EasyEdit\convert\BlockStateConvertor;
use platz1de\EasyEdit\convert\TileConvertor;
use platz1de\EasyEdit\Messages;
use pocketmine\block\Block;
use pocketmine\block\tile\Tile;

class BlockInfoTool
{
	/**
	 * @param string $player
	 * @param Block  $block
	 */
	public static function display(string $player, Block $block): void
	{
		$state = BlockStateConvertor::getState($block->getFullId());
		if (($t = $block->getPosition()->getWorld()->getTile($block->getPosition())) instanceof Tile) {
			$tile = $t->saveNBT();
			if (TileConvertor::toJava($block->getFullId(), $tile)) {
				$state = BlockStateConvertor::processTileData($state, $tile);
			}
		}
		Messages::send($player, "block-info", [
			"{id}" => (string) $block->getId(),
			"{meta}" => (string) $block->getMeta(),
			"{name}" => $block->getName(),
			"{x}" => (string) $block->getPosition()->getX(),
			"{y}" => (string) $block->getPosition()->getY(),
			"{z}" => (string) $block->getPosition()->getZ(),
			"{java_state}" => $state
		]);
	}
}