<?php

namespace platz1de\EasyEdit\convert\tile;

use InvalidArgumentException;
use pocketmine\block\utils\DyeColor;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\data\bedrock\block\convert\UnsupportedBlockStateException;
use pocketmine\data\bedrock\DyeColorIdMap;
use pocketmine\nbt\tag\StringTag;
use UnexpectedValueException;

abstract class ColoredTileConvertor extends TileConvertorPiece
{
	private const INTERNAL_TAG_COLOR = "color";

	public function parseColor(BlockStateData $state): DyeColor
	{
		$color = $state->getStates()[self::INTERNAL_TAG_COLOR] ?? null;
		if (!$color instanceof StringTag) {
			throw new UnsupportedBlockStateException("Missing color tag");
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
		return $color;
	}

	public function putNormalColor(BlockStateData $state, int $color): BlockStateData
	{
		$javaColor = DyeColorIdMap::getInstance()->fromId($color)?->name();
		if ($javaColor === null) {
			throw new UnexpectedValueException("Invalid color: " . $color);
		}
		return $this->putColor($state, $javaColor);
	}

	public function putInvertedColor(BlockStateData $state, int $color): BlockStateData
	{
		$javaColor = DyeColorIdMap::getInstance()->fromInvertedId($color)?->name();
		if ($javaColor === null) {
			throw new UnsupportedBlockStateException("Invalid color: " . $color);
		}
		return $this->putColor($state, $javaColor);
	}

	private function putColor(BlockStateData $state, string $color): BlockStateData
	{
		$states = $state->getStates();
		$states[self::INTERNAL_TAG_COLOR] = new StringTag($color);
		return new BlockStateData($state->getName(), $states, $state->getVersion());
	}
}