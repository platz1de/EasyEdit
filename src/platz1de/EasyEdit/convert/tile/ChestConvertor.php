<?php

namespace platz1de\EasyEdit\convert\tile;

use platz1de\EasyEdit\convert\TileConvertor;
use pocketmine\block\Block;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\tile\Chest;
use pocketmine\block\tile\Tile;
use pocketmine\nbt\tag\CompoundTag;

class ChestConvertor extends InventoryConvertor
{
	public static function toBedrock(CompoundTag $tile): void
	{
		$tile->setString(Tile::TAG_ID, TileConvertor::TILE_CHEST); //Trapped chests are using chest tiles
		if (isset($tile->getValue()[Chest::TAG_PAIRX], $tile->getValue()[Chest::TAG_PAIRZ])) {
			$tile->setInt(Chest::TAG_PAIRX, $tile->getInt(Chest::TAG_PAIRX) + $tile->getInt(Tile::TAG_X));
			$tile->setInt(Chest::TAG_PAIRZ, $tile->getInt(Chest::TAG_PAIRZ) + $tile->getInt(Tile::TAG_Z));
		}
		parent::toBedrock($tile);
	}

	public static function toJava(int $blockId, CompoundTag $tile): void
	{
		if ($blockId >> Block::INTERNAL_METADATA_BITS === BlockLegacyIds::TRAPPED_CHEST) {
			$tile->setString(Tile::TAG_ID, TileConvertor::TILE_TRAPPED_CHEST); //pmmp uses the same tile here
		}
		if (isset($tile->getValue()[Chest::TAG_PAIRX], $tile->getValue()[Chest::TAG_PAIRZ])) {
			$tile->setInt(Chest::TAG_PAIRX, $tile->getInt(Chest::TAG_PAIRX) - $tile->getInt(Tile::TAG_X));
			$tile->setInt(Chest::TAG_PAIRZ, $tile->getInt(Chest::TAG_PAIRZ) - $tile->getInt(Tile::TAG_Z));
		}
		parent::toJava($blockId, $tile);
	}
}