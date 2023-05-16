<?php

namespace platz1de\EasyEdit\convert\tile;

use InvalidArgumentException;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\utils\SkullType;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use UnexpectedValueException;

//TODO: 1.20: Note block tunes
class MobHeadTileConvertor extends TileConvertorPiece
{
	private const INTERNAL_TAG_TYPE = "type";
	private const INTERNAL_TAG_ATTACHMENT = "attachment"; //not really needed for tile conversion
	private const INTERNAL_TAG_ROTATION = "rot";

	private const JAVA_UNSUPPORTED_TAG_EXTRA_TYPE = "ExtraType";
	private const JAVA_UNSUPPORTED_TAG_OWNER = "SkullOwner";

	//Skull constants are private...
	private const TAG_SKULL_TYPE = "SkullType";
	private const TAG_ROT = "Rot";
	private const TAG_MOUTH_MOVING = "MouthMoving"; //unused
	private const TAG_MOUTH_TICK_COUNT = "MouthTickCount"; //unused

	public function preprocessTileState(BlockStateData $state): ?CompoundTag
	{
		$type = $state->getStates()[self::INTERNAL_TAG_TYPE] ?? null;
		$rotation = $state->getStates()[self::INTERNAL_TAG_ROTATION] ?? new IntTag(0);
		if (!$type instanceof StringTag || !$rotation instanceof IntTag) {
			return null;
		}
		$javaSkullType = $type->getValue();
		try {
			/**
			 * @var SkullType $skullType
			 */
			$skullType = DyeColor::__callStatic($javaSkullType, []);
		} catch (InvalidArgumentException) {
			throw new UnexpectedValueException("Invalid skull type: " . $javaSkullType);
		}
		return CompoundTag::create()
			->setByte(self::TAG_SKULL_TYPE, $skullType->getMagicNumber())
			->setByte(self::TAG_ROT, $rotation->getValue());
	}

	public function toBedrock(CompoundTag $tile): void
	{
		parent::toBedrock($tile);
		$tile->removeTag(self::JAVA_UNSUPPORTED_TAG_OWNER, self::JAVA_UNSUPPORTED_TAG_EXTRA_TYPE);
	}

	public function toJava(CompoundTag $tile, BlockStateData $state): ?BlockStateData
	{
		parent::toJava($tile, $state);
		$type = $tile->getByte(self::TAG_SKULL_TYPE, -1);
		$javaSkullType = SkullType::fromMagicNumber($type)->name();
		$states = $state->getStates();
		$states[self::INTERNAL_TAG_TYPE] = new StringTag($javaSkullType);

		$isOnFloor = match ($states["facing_direction"]->getValue()) {
			0, 1 => true, //0 shouldn't be used
			2, 3, 4, 5 => false,
			default => throw new UnexpectedValueException("Invalid facing direction: " . $states["facing_direction"]->getValue())
		};
		$states[self::INTERNAL_TAG_ATTACHMENT] = new StringTag($isOnFloor ? "floor" : "wall");
		$states[self::INTERNAL_TAG_ROTATION] = new IntTag($tile->getByte(self::TAG_ROT, 0));
		return new BlockStateData($state->getName(), $states, $state->getVersion());
	}
}