<?php

namespace platz1de\EasyEdit\world\clientblock;

use pocketmine\block\Block;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\data\bedrock\block\BlockStateNames;
use pocketmine\data\bedrock\block\BlockStateSerializeException;
use pocketmine\data\bedrock\block\BlockStateStringValues;
use pocketmine\data\bedrock\block\BlockTypeNames;
use pocketmine\data\bedrock\block\convert\BlockStateWriter;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\CloningRegistryTrait;
use pocketmine\world\format\io\GlobalBlockStateHandlers;

/**
 * @method static CompoundBlock OUTLINE_BLOCK()
 * @method static CompoundBlock WINDOW_BLOCK()
 */
class Registry
{
	use CloningRegistryTrait;

	protected static function setup(): void
	{
		self::_registryRegister("outline_block", new CompoundBlock(3, 0, CompoundTag::create()->setByte("showBoundingBox", 1)));
		self::_registryRegister("window_block", new CompoundBlock(3, 5, CompoundTag::create()
			->setString("id", "StructureBlock")
			->setString("structureName", "clipboard")
			->setString("dataField", "")
			->setInt("data", 5)
			->setByte("rotation", 0)
			->setByte("mirror", 0)
			->setFloat("integrity", 100.0)
			->setLong("seed", 0)
			->setByte("ignoreEntities", 1)
			->setByte("includePlayers", 0)
			->setByte("removeBlocks", 0)
			->setByte("showBoundingBox", 0)));
	}

	public static function registerToNetwork(): void
	{
		/**
		 * @var Block $block
		 */
		foreach (self::_registryGetAll() as $block) {
			RuntimeBlockStateRegistry::getInstance()->register($block);

			//Note: not registering to Deserializer
			GlobalBlockStateHandlers::getSerializer()->map($block, function (CompoundBlock $block): BlockStateWriter {
				return BlockStateWriter::create(BlockTypeNames::STRUCTURE_BLOCK)
					->writeString(BlockStateNames::STRUCTURE_BLOCK_TYPE, match ($type = $block->getType()) {
						0 => BlockStateStringValues::STRUCTURE_BLOCK_TYPE_DATA,
						1 => BlockStateStringValues::STRUCTURE_BLOCK_TYPE_SAVE,
						2 => BlockStateStringValues::STRUCTURE_BLOCK_TYPE_LOAD,
						3 => BlockStateStringValues::STRUCTURE_BLOCK_TYPE_CORNER,
						4 => BlockStateStringValues::STRUCTURE_BLOCK_TYPE_INVALID,
						5 => BlockStateStringValues::STRUCTURE_BLOCK_TYPE_EXPORT,
						default => throw new BlockStateSerializeException("Invalid Structure Block mode {$type}"),
					});
			});
		}
	}
}