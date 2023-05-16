<?php

namespace platz1de\EasyEdit\convert\tile;

use InvalidArgumentException;
use pocketmine\block\tile\Banner;
use pocketmine\block\utils\DyeColor;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\data\bedrock\DyeColorIdMap;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use UnexpectedValueException;

class BannerTileConvertor extends TileConvertorPiece
{
	private const INTERNAL_TAG_COLOR = "color";
	private const JAVA_UNSUPPORTED_CUSTOM_NAME = "CustomName";

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
			->setInt(Banner::TAG_BASE, DyeColorIdMap::getInstance()->toInvertedId($color));
	}

	public function toBedrock(CompoundTag $tile): void
	{
		parent::toBedrock($tile);
		//TODO: apparently there is a integer to indicate the banner type (ominous / normal), not supported by pmmp though
		$tile->removeTag(self::JAVA_UNSUPPORTED_CUSTOM_NAME);
	}

	public function toJava(CompoundTag $tile, BlockStateData $state): ?BlockStateData
	{
		parent::toJava($tile, $state);
		$color = $tile->getInt(Banner::TAG_BASE, -1);
		$javaColor = DyeColorIdMap::getInstance()->fromInvertedId($color)?->name();
		if ($javaColor === null) {
			return null;
		}
		$states = $state->getStates();
		$states[self::INTERNAL_TAG_COLOR] = new StringTag($javaColor);
		return new BlockStateData($state->getName(), $states, $state->getVersion());
	}
}