<?php

namespace platz1de\EasyEdit\convert\tile;

use pocketmine\block\Note as BlockNote;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use UnexpectedValueException;

class NoteBlockTileConvertor extends TileConvertorPiece
{
	private const JAVA_TAG_NOTE = "note";

	private const TAG_NOTE = "note";

	public function preprocessTileState(BlockStateData $state): ?CompoundTag
	{
		$note = $state->getStates()[self::JAVA_TAG_NOTE] ?? null;
		if (!$note instanceof IntTag) {
			return null;
		}
		return CompoundTag::create()
			->setByte(self::TAG_NOTE, $note->getValue());
	}

	public function toJava(CompoundTag $tile, BlockStateData $state): ?BlockStateData
	{
		parent::toJava($tile, $state);
		//TODO: write instrument here, when pmmp supports them properly
		$pitch = $tile->getByte(self::TAG_NOTE, -1);
		if ($pitch < BlockNote::MIN_PITCH || $pitch > BlockNote::MAX_PITCH) {
			throw new UnexpectedValueException("Invalid pitch: " . $pitch);
		}
		$states = $state->getStates();
		$states[self::JAVA_TAG_NOTE] = new IntTag($pitch);
		return new BlockStateData($state->getName(), $states, $state->getVersion());
	}

	public function hasJavaCounterpart(): bool
	{
		return false;
	}
}