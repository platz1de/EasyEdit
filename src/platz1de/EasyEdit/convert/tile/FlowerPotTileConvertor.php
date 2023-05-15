<?php

namespace platz1de\EasyEdit\convert\tile;

use platz1de\EasyEdit\utils\BlockParser;
use platz1de\EasyEdit\utils\RepoManager;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;

class FlowerPotTileConvertor extends TileConvertorPiece
{
	private const INTERNAL_TAG_TYPE = "type";
	private const INTERNAL_TYPE_EMPTY = "none";

	private const TAG_PLANT_BLOCK = "PlantBlock";

	public function preprocessTileState(BlockStateData $state): ?CompoundTag
	{
		$type = $state->getStates()[self::INTERNAL_TAG_TYPE] ?? null;
		if (!$type instanceof StringTag || $type->getValue() === self::INTERNAL_TYPE_EMPTY) {
			return null;
		}
		return CompoundTag::create()
			->setTag(self::TAG_PLANT_BLOCK, BlockParser::fromStateString($type->getValue(), RepoManager::getVersion())->toNbt());
	}

	public function toBedrock(CompoundTag $tile): void { }

	public function toJava(CompoundTag $tile, BlockStateData $state): ?BlockStateData
	{
		$type = $tile->getCompoundTag(self::TAG_PLANT_BLOCK);
		if ($type === null) {
			$javaType = self::INTERNAL_TYPE_EMPTY;
		} else {
			$javaType = BlockParser::toStateString(BlockStateData::fromNbt($type));
		}
		$states = $state->getStates();
		$states[self::INTERNAL_TAG_TYPE] = new StringTag($javaType);
		return new BlockStateData($state->getName(), $states, $state->getVersion());
	}
}