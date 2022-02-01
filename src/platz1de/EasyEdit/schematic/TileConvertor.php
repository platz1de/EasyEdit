<?php

namespace platz1de\EasyEdit\schematic;

use JsonException;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\thread\EditThread;
use pocketmine\block\tile\ShulkerBox;
use pocketmine\block\tile\Sign;
use pocketmine\block\tile\Tile;
use pocketmine\math\Facing;
use pocketmine\nbt\tag\CompoundTag;
use UnexpectedValueException;

class TileConvertor
{
	public const DATA_CHEST_RELATION = "chest_relation";

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
	public static function toBedrock(CompoundTag $tile, BlockListSelection $selection, ConvertorCache $cache): void
	{
		//some of these aren't actually part of pmmp yet, but plugins might use them
		switch ($tile->getString(Tile::TAG_ID)) {
			case self::TILE_SIGN:
				//TODO: glowing & color
				$text = [];
				for ($i = 1; $i <= 4; $i++) {
					$line = $tile->getString("Text" . $i);
					try {
						/** @var string[] $json */
						$json = json_decode($line, true, 2, JSON_THROW_ON_ERROR);
						$text[] = $json["text"];
					} catch (JsonException) {
						throw new UnexpectedValueException("Invalid JSON in sign text: " . $line);
					}
				}
				$tile->removeTag("Text1", "Text2", "Text3", "Text4");
				$tile->setString(Sign::TAG_TEXT_BLOB, implode("\n", $text));
				break;
			case self::TILE_CHEST:
				//TODO: Some items need to be converted
				//TODO: double chests are saved with their blockstate in java
				break;
			case self::TILE_TRAPPED_CHEST:
				//TODO: see chest
				$tile->setString(Tile::TAG_ID, self::TILE_CHEST); //pmmp uses the same tile here
				break;
			case self::TILE_SHULKER_BOX:
				//TODO: shulker facing is saved as a blockstate in java
				$tile->setByte(ShulkerBox::TAG_FACING, Facing::UP);
				//TODO: convert items
				break;
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