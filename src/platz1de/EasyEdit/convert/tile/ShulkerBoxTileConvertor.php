<?php

namespace platz1de\EasyEdit\convert\tile;

use InvalidArgumentException;
use pocketmine\block\tile\ShulkerBox;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\math\Facing;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;

class ShulkerBoxTileConvertor extends ContainerTileConvertor
{
	public function preprocessTileState(BlockStateData $state): ?CompoundTag
	{
		$facing = $state->getStates()["facing"] ?? null;
		if (!$facing instanceof StringTag) {
			return null;
		}
		$facing = $facing->getValue();
		return CompoundTag::create()
			->setByte(ShulkerBox::TAG_FACING, match ($facing) {
				"down" => Facing::DOWN,
				"up" => Facing::UP,
				"north" => Facing::NORTH,
				"south" => Facing::SOUTH,
				"west" => Facing::WEST,
				"east" => Facing::EAST,
				default => throw new InvalidArgumentException("Invalid shulker box facing: $facing")
			});
	}

	public function toJava(CompoundTag $tile, BlockStateData $state): ?BlockStateData
	{
		parent::toJava($tile, $state);

		$facing = $tile->getByte(ShulkerBox::TAG_FACING, 0);
		$type = match ($facing) {
			Facing::DOWN => "down",
			Facing::UP => "up",
			Facing::NORTH => "north",
			Facing::SOUTH => "south",
			Facing::WEST => "west",
			Facing::EAST => "east",
			default => throw new InvalidArgumentException("Invalid shulker box facing: $facing")
		};
		$states = $state->getStates();
		$states["facing"] = new StringTag($type);
		return new BlockStateData($state->getName(), $states, $state->getVersion());
	}
}