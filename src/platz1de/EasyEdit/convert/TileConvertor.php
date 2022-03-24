<?php

namespace platz1de\EasyEdit\convert;

use platz1de\EasyEdit\convert\tile\ChestConvertor;
use platz1de\EasyEdit\convert\tile\InventoryConvertor;
use platz1de\EasyEdit\convert\tile\SignConvertor;
use platz1de\EasyEdit\convert\tile\TileConvertorPiece;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\thread\EditThread;
use pocketmine\block\tile\Chest;
use pocketmine\block\tile\Hopper;
use pocketmine\block\tile\ShulkerBox;
use pocketmine\block\tile\Sign;
use pocketmine\block\tile\Skull;
use pocketmine\block\tile\Tile;
use pocketmine\block\tile\TileFactory;
use pocketmine\nbt\tag\CompoundTag;
use Throwable;
use UnexpectedValueException;

class TileConvertor
{
	public const DATA_CHEST_RELATION = "chest_relation";
	public const DATA_SHULKER_BOX_FACING = "shulker_box_facing";
	public const DATA_SKULL_TYPE = "skull_type";
	public const DATA_SKULL_ROTATION = "skull_rotation";

	public const TILE_CHEST = "minecraft:chest";
	public const TILE_DISPENSER = "minecraft:dispenser";
	public const TILE_DROPPER = "minecraft:dropper";
	public const TILE_HOPPER = "minecraft:hopper";
	public const TILE_SHULKER_BOX = "minecraft:shulker_box";
	public const TILE_SIGN = "minecraft:sign";
	public const TILE_SKULL = "minecraft:skull";
	public const TILE_TRAPPED_CHEST = "minecraft:trapped_chest";

	/**
	 * TODO: Add all of the tiles underneath
	 * Beehive
	 * Bee Nest
	 * Banners
	 * Furnace
	 * Brewing Stand
	 * Barrel
	 * Smoker
	 * Blast Furnace
	 * Campfire
	 * Soul Campfire
	 * Lectern
	 * Beacon
	 * Spawner
	 * Note Block (blockstate in java)
	 * Piston -> Moving Piston
	 * Jukebox
	 * Enchanting Table
	 * End Portal
	 * Ender Chest
	 * Command Block
	 * End Gateway
	 * Structure Block
	 * Jigsaw Block
	 * Nether Reactor Core
	 * Daylight Sensor
	 * Flower Pot (blockstate in java)
	 * Redstone Comparator
	 * Bed
	 * Cauldron (blockstate in java)
	 * Conduit
	 * Bell
	 * Lodestone
	 *
	 * Item Frame (entity in java)
	 */

	/**
	 * @param CompoundTag        $tile
	 * @param BlockListSelection $selection
	 * @param CompoundTag|null   $extraData
	 */
	public static function toBedrock(CompoundTag $tile, BlockListSelection $selection, ?CompoundTag $extraData): void
	{
		//some of these aren't actually part of pmmp yet, but plugins might use them
		if ($extraData !== null) {
			foreach ($extraData->getValue() as $key => $value) {
				$tile->setTag($key, $value);
			}
		}
		try {
			try {
				$class = self::getConvertor($tile->getString(Tile::TAG_ID));
			} catch (UnexpectedValueException) {
				EditThread::getInstance()->debug("Found unknown tile " . $tile->getString(Tile::TAG_ID));
				return;
			}
			if ($class !== null) {
				$class::toBedrock($tile);
			}
			$selection->addTile($tile);
		} catch (Throwable $exception) {
			EditThread::getInstance()->debug("Found malformed tile " . $tile->getString(Tile::TAG_ID) . ": " . $exception->getMessage());
			return;
		}
	}

	/**
	 * @param int         $blockId
	 * @param CompoundTag $tile
	 * @return bool
	 */
	public static function toJava(int $blockId, CompoundTag $tile): bool
	{
		$tile->setString(Tile::TAG_ID, self::getJavaId($tile->getString(Tile::TAG_ID)));
		try {
			$class = self::getConvertor($tile->getString(Tile::TAG_ID));
		} catch (UnexpectedValueException) {
			EditThread::getInstance()->debug("Found unknown tile " . $tile->getString(Tile::TAG_ID));
			return false;
		}
		if ($class !== null) {
			$class::toJava($blockId, $tile);
		}
		return true;
	}

	/**
	 * @param string $tile
	 * @return ?class-string<TileConvertorPiece>
	 */
	public static function getConvertor(string $tile): ?string
	{
		return match ($tile) {
			self::TILE_SIGN => SignConvertor::class,
			self::TILE_CHEST, self::TILE_TRAPPED_CHEST => ChestConvertor::class,
			self::TILE_SHULKER_BOX, self::TILE_DISPENSER, self::TILE_DROPPER, self::TILE_HOPPER => InventoryConvertor::class,
			self::TILE_SKULL => null,
			default => throw new UnexpectedValueException("Unknown tile " . $tile)
		};
	}

	/**
	 * @param string $tile
	 * @return string
	 */
	public static function getJavaId(string $tile): string
	{
		return match ($tile) {
			TileFactory::getInstance()->getSaveId(Chest::class) => self::TILE_CHEST,
			"Dispenser" => self::TILE_DISPENSER,
			"Dropper" => self::TILE_DROPPER,
			TileFactory::getInstance()->getSaveId(Hopper::class) => self::TILE_HOPPER,
			TileFactory::getInstance()->getSaveId(ShulkerBox::class) => self::TILE_SHULKER_BOX,
			TileFactory::getInstance()->getSaveId(Sign::class) => self::TILE_SIGN,
			TileFactory::getInstance()->getSaveId(Skull::class) => self::TILE_SKULL,
			default => $tile //just attempt it
		};
	}
}