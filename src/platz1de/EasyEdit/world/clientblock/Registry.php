<?php

namespace platz1de\EasyEdit\world\clientblock;

use pocketmine\block\Block;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\data\bedrock\block\BlockTypeNames;
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
		self::_registryRegister("outline_block", new CompoundBlock(CompoundTag::create()->setByte("showBoundingBox", 1)));
		self::_registryRegister("window_block", new CompoundBlock(CompoundTag::create()
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
			GlobalBlockStateHandlers::getSerializer()->mapSimple($block, BlockTypeNames::STRUCTURE_BLOCK);
		}
	}
}