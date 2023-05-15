<?php

namespace platz1de\EasyEdit\convert\tile;

use InvalidArgumentException;
use pocketmine\block\tile\Bed;
use pocketmine\block\utils\DyeColor;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\data\bedrock\DyeColorIdMap;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use UnexpectedValueException;

class BedTileConvertor extends TileConvertorPiece
{
	private const INTERNAL_TAG_COLOR = "color";

	public function preprocessTileState(BlockStateData $state): ?CompoundTag
	{
		$color = $state->getStates()[self::INTERNAL_TAG_COLOR] ?? null;
		if (!$color instanceof StringTag) {
			return null;
		}
		$javaColor = $color->getValue();
		try {
			/**
			 * @var DyeColor $color
			 */
			$color = DyeColor::__callStatic($javaColor, []);
		} catch (InvalidArgumentException) {
			throw new UnexpectedValueException("Invalid color: " . $javaColor);
		}
		return CompoundTag::create()
			->setInt(Bed::TAG_COLOR, DyeColorIdMap::getInstance()->toId($color));
	}

	public function toBedrock(CompoundTag $tile): void { }

	public function toJava(CompoundTag $tile, BlockStateData $state): ?BlockStateData
	{
		$color = $tile->getInt(Bed::TAG_COLOR, -1);
		$javaColor = DyeColorIdMap::getInstance()->fromId($color)?->name();
		if ($javaColor === null) {
			return null;
		}
		$states = $state->getStates();
		$states[self::INTERNAL_TAG_COLOR] = new StringTag($javaColor);
		return new BlockStateData($state->getName(), $states, $state->getVersion());
	}
}