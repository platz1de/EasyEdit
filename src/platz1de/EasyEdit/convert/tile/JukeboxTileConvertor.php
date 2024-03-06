<?php

namespace platz1de\EasyEdit\convert\tile;

use platz1de\EasyEdit\convert\ItemConvertor;
use platz1de\EasyEdit\schematic\nbt\AbstractNBT;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\nbt\tag\CompoundTag;

class JukeboxTileConvertor extends TileConvertorPiece
{
	private const JAVA_TAG_IS_PLAYING = "IsPlaying";
	private const JAVA_TAG_RECORD_ITEM = "RecordItem";
	private const JAVA_TAG_START_TICK = "RecordStartTick";
	private const JAVA_TAG_TICK_COUNT = "TickCount";

	private const TAG_RECORD = "RecordItem";

	public function __construct(string $bedrockName, private string $secondaryBedrockName, string $javaName)
	{
		parent::__construct($bedrockName, $javaName);
	}

	public function preprocessTileState(BlockStateData $state): ?CompoundTag
	{
		return null;
	}

	public function toBedrock(CompoundTag $tile): void
	{
		parent::toBedrock($tile);
		$tile->removeTag(self::JAVA_TAG_IS_PLAYING, self::JAVA_TAG_START_TICK, self::JAVA_TAG_TICK_COUNT);
		$item = AbstractNBT::fromAbstract($tile->getTag(self::JAVA_TAG_RECORD_ITEM));
		if (!$item instanceof CompoundTag) {
			return;
		}
		$converted = ItemConvertor::convertItemBedrock($item);
		if ($converted !== null) {
			$tile->setTag(self::TAG_RECORD, $converted);
		}
	}

	public function toJava(CompoundTag $tile, BlockStateData $state): ?BlockStateData
	{
		parent::toJava($tile, $state);
		$tile->setByte(self::JAVA_TAG_IS_PLAYING, 0);
		$tile->setInt(self::JAVA_TAG_START_TICK, 0);
		$tile->setInt(self::JAVA_TAG_TICK_COUNT, 0);
		$item = $tile->getTag(self::TAG_RECORD);
		if (!$item instanceof CompoundTag) {
			return null;
		}
		$converted = ItemConvertor::convertItemJava($item);
		if ($converted !== null) {
			$tile->setTag(self::JAVA_TAG_RECORD_ITEM, $converted);
		}
		return null;
	}

	/**
	 * @return string[]
	 */
	public function getIdentifiers(): array
	{
		$identifiers = parent::getIdentifiers();
		$identifiers[] = $this->secondaryBedrockName;
		return $identifiers;
	}
}