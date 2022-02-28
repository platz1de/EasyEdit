<?php

namespace platz1de\EasyEdit\schematic;

use JsonException;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\thread\EditThread;
use pocketmine\block\tile\Chest;
use pocketmine\block\tile\Tile;
use pocketmine\nbt\tag\CompoundTag;
use UnexpectedValueException;

class TileConvertor
{
	public const DATA_CHEST_RELATION = "chest_relation";
	public const DATA_SHULKER_BOX_FACING = "shulker_box_facing";

	public const TILE_CHEST = "minecraft:chest";
	public const TILE_DISPENSER = "minecraft:dispenser";
	public const TILE_DROPPER = "minecraft:dropper";
	public const TILE_HOPPER = "minecraft:hopper";
	public const TILE_SHULKER_BOX = "minecraft:shulker_box";
	public const TILE_SIGN = "minecraft:sign";
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
	 * Mob Head
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
		switch ($tile->getString(Tile::TAG_ID)) {
			case self::TILE_SIGN:
				//TODO: glowing & color
				for ($i = 1; $i <= 4; $i++) {
					$line = $tile->getString("Text" . $i);
					try {
						/** @var string[] $json */
						$json = json_decode($line, true, 2, JSON_THROW_ON_ERROR);
						if (!isset($json["text"])) {
							throw new JsonException("Missing text key");
						}
						$text = $json["text"];
					} catch (JsonException) {
						throw new UnexpectedValueException("Invalid JSON in sign text: " . $line);
					}
					$tile->setString("Text" . $i, $text);
				}
				break;
			/** @noinspection PhpMissingBreakStatementInspection */
			case self::TILE_TRAPPED_CHEST:
				$tile->setString(Tile::TAG_ID, self::TILE_CHEST); //pmmp uses the same tile here
			case self::TILE_CHEST:
				//TODO: Some items need to be converted
				if (isset($tile->getValue()[Chest::TAG_PAIRX], $tile->getValue()[Chest::TAG_PAIRZ])) {
					$tile->setInt(Chest::TAG_PAIRX, $tile->getInt(Chest::TAG_PAIRX) + $tile->getInt(Tile::TAG_X));
					$tile->setInt(Chest::TAG_PAIRZ, $tile->getInt(Chest::TAG_PAIRZ) + $tile->getInt(Tile::TAG_Z));
				}
				break;
			case self::TILE_SHULKER_BOX:
			case self::TILE_DISPENSER:
			case self::TILE_DROPPER:
			case self::TILE_HOPPER:
				//TODO: convert items
				break;
			default:
				EditThread::getInstance()->debug("Found unknown tile " . $tile->getString(Tile::TAG_ID));
				return;
		}
		$selection->addTile($tile);
	}
}