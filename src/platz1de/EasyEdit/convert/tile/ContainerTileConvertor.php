<?php

namespace platz1de\EasyEdit\convert\tile;

use platz1de\EasyEdit\convert\ItemConvertor;
use platz1de\EasyEdit\schematic\nbt\AbstractListTag;
use pocketmine\block\tile\Container;
use pocketmine\block\tile\Tile;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use UnexpectedValueException;

//TODO: LootTables (according to the wiki java seed uses a long while bedrock uses an int, no idea whether this is correct though)
//TODO: Test color codes of lock items (might need conversion)
class ContainerTileConvertor extends TileConvertorPiece
{
	public function preprocessTileState(BlockStateData $state): ?CompoundTag
	{
		return null;
	}

	public function toBedrock(CompoundTag $tile): void
	{
		$tile->setString(Tile::TAG_ID, $this->bedrockName);
		$items = $tile->getTag(Container::TAG_ITEMS);
		if (!$items instanceof AbstractListTag) {
			return;
		}
		$tile->setTag(Container::TAG_ITEMS, $new = new ListTag([], NBT::TAG_Compound));
		$count = $items->getLength();
		for ($i = 0; $i < $count; $i++) {
			$item = $items->next();
			if (!$item instanceof CompoundTag) {
				throw new UnexpectedValueException("Items need to be represented as compound tags");
			}
			$converted = ItemConvertor::convertItemBedrock($item);
			if ($converted !== null) {
				$new->push($converted);
			}
		}
	}

	public function toJava(CompoundTag $tile, BlockStateData $state): ?BlockStateData
	{
		$tile->setString(Tile::TAG_ID, $this->javaName);
		$items = $tile->getListTag(Container::TAG_ITEMS);
		if ($items === null) {
			return null;
		}
		$tile->setTag(Container::TAG_ITEMS, $new = new ListTag([], NBT::TAG_Compound));
		foreach ($items as $item) {
			if (!$item instanceof CompoundTag) {
				throw new UnexpectedValueException("Items need to be represented as compound tags");
			}
			$converted = ItemConvertor::convertItemJava($item);
			if ($converted !== null) {
				$new->push($converted);
			}
		}
		return null;
	}
}