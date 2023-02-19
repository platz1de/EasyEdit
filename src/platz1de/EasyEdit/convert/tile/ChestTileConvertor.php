<?php

namespace platz1de\EasyEdit\convert\tile;

use InvalidArgumentException;
use pocketmine\block\tile\Chest;
use pocketmine\block\tile\Tile;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\data\bedrock\block\BlockTypeNames;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;

class ChestTileConvertor extends ContainerTileConvertor
{
	protected string $javaNameTrapped;

	public function __construct(string $bedrockName, string $javaNameRegular, string $javaNameTrapped)
	{
		parent::__construct($bedrockName, $javaNameRegular);
		$this->javaNameTrapped = $javaNameTrapped;
	}

	public function preprocessTileState(BlockStateData $state): ?CompoundTag
	{
		$type = $state->getStates()["type"] ?? null;
		$facing = $state->getStates()["facing"] ?? null;
		if (!$type instanceof StringTag || !$facing instanceof StringTag) {
			return null;
		}
		$type = $type->getValue();
		$facing = $facing->getValue();
		if ($type === "single") {
			return null;
		}
		$pairFacing = match (true) {
			$type === "left" && $facing === "north", $type === "right" && $facing === "south" => Facing::WEST,
			$type === "left" && $facing === "east", $type === "right" && $facing === "west" => Facing::NORTH,
			$type === "left" && $facing === "south", $type === "right" && $facing === "north" => Facing::EAST,
			$type === "left" && $facing === "west", $type === "right" && $facing === "east" => Facing::SOUTH,
			default => throw new InvalidArgumentException("Invalid chest type: $type $facing")
		};
		$vector = Vector3::zero()->getSide($pairFacing);
		return CompoundTag::create()
			->setInt(Chest::TAG_PAIRX, $vector->getFloorX())
			->setInt(Chest::TAG_PAIRZ, $vector->getFloorZ());
	}

	public function toBedrock(CompoundTag $tile): void
	{
		parent::toBedrock($tile);
		if (isset($tile->getValue()[Chest::TAG_PAIRX], $tile->getValue()[Chest::TAG_PAIRZ])) {
			$tile->setInt(Chest::TAG_PAIRX, $tile->getInt(Chest::TAG_PAIRX) + $tile->getInt(Tile::TAG_X));
			$tile->setInt(Chest::TAG_PAIRZ, $tile->getInt(Chest::TAG_PAIRZ) + $tile->getInt(Tile::TAG_Z));
		}
	}

	public function toJava(CompoundTag $tile, BlockStateData $state): ?BlockStateData
	{
		parent::toJava($tile, $state);
		if ($state->getName() === BlockTypeNames::TRAPPED_CHEST) {
			$tile->setString(Tile::TAG_ID, $this->javaNameTrapped); //pmmp uses the same tile here
		}
		if (!isset($tile->getValue()[Chest::TAG_PAIRX], $tile->getValue()[Chest::TAG_PAIRZ])) {
			return null;
		}

		$pairX = $tile->getInt(Chest::TAG_PAIRX) - $tile->getInt(Tile::TAG_X);
		$pairZ = $tile->getInt(Chest::TAG_PAIRZ) - $tile->getInt(Tile::TAG_Z);
		$pairFacing = match (true) {
			$pairX === 0 && $pairZ === -1 => Facing::NORTH,
			$pairX === 1 && $pairZ === 0 => Facing::EAST,
			$pairX === 0 && $pairZ === 1 => Facing::SOUTH,
			$pairX === -1 && $pairZ === 0 => Facing::WEST,
			default => throw new InvalidArgumentException("Invalid chest pair: $pairX $pairZ")
		};
		$facing = $state->getStates()["facing"] ?? null;
		if (!$facing instanceof StringTag) {
			return null;
		}
		$facing = $facing->getValue();
		$type = match (true) {
			$pairFacing === Facing::NORTH && $facing === "north", $pairFacing === Facing::SOUTH && $facing === "south", $pairFacing === Facing::EAST && $facing === "east", $pairFacing === Facing::WEST && $facing === "west" => "left",
			$pairFacing === Facing::NORTH && $facing === "south", $pairFacing === Facing::SOUTH && $facing === "north", $pairFacing === Facing::EAST && $facing === "west", $pairFacing === Facing::WEST && $facing === "east" => "right",
			default => throw new InvalidArgumentException("Invalid chest type: $pairFacing $facing")
		};
		$states = $state->getStates();
		$states["type"] = new StringTag($type);
		return new BlockStateData($state->getName(), $states, $state->getVersion());
	}
}