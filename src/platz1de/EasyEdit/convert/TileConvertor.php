<?php

namespace platz1de\EasyEdit\convert;

use InvalidArgumentException;
use platz1de\EasyEdit\convert\tile\ChestTileConvertor;
use platz1de\EasyEdit\convert\tile\ContainerTileConvertor;
use platz1de\EasyEdit\convert\tile\SignConvertor;
use platz1de\EasyEdit\convert\tile\TileConvertorPiece;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\utils\BlockParser;
use platz1de\EasyEdit\utils\RepoManager;
use pocketmine\block\tile\Tile;
use pocketmine\nbt\tag\CompoundTag;
use Throwable;

class TileConvertor
{
	public const PREPROCESSED_TYPE = "EasyEditTileType";

	/**
	 * TODO: Add all the tiles underneath
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
	 * Lodestone (compass contains coordinates in java, shared id in bedrock)
	 * Chiseled Bookshelf (1.20)
	 * Skull
	 *
	 * Item Frame (entity in java)
	 */
	/**
	 * @var array<string, TileConvertorPiece>
	 */
	private static array $convertors = [];

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
				if ($key === Tile::TAG_ID) {
					continue;
				}
				$tile->setTag($key, $value);
			}
		}
		if (!isset(self::$convertors[$tile->getString(Tile::TAG_ID)])) {
			EditThread::getInstance()->debug("Found unknown tile " . $tile->getString(Tile::TAG_ID));
			return;
		}
		if ($extraData !== null && $extraData->getString(self::PREPROCESSED_TYPE) !== self::$convertors[$tile->getString(Tile::TAG_ID)]::class) {
			throw new InvalidArgumentException("Preprocessed tile type doesn't match");
		}
		try {
			self::$convertors[$tile->getString(Tile::TAG_ID)]->toBedrock($tile);
			$selection->addTile($tile);
		} catch (Throwable $exception) {
			EditThread::getInstance()->debug("Found malformed tile " . $tile->getString(Tile::TAG_ID) . ": " . $exception->getMessage());
		}
	}

	/**
	 * @param CompoundTag $tile
	 * @param string      $state
	 * @return bool Whether the tile should be included
	 */
	public static function toJava(CompoundTag $tile, string &$state): bool
	{
		if (!isset(self::$convertors[$tile->getString(Tile::TAG_ID)])) {
			EditThread::getInstance()->debug("Found unknown tile " . $tile->getString(Tile::TAG_ID));
			return false;
		}
		try {
			$newState = self::$convertors[$tile->getString(Tile::TAG_ID)]->toJava($tile, BlockParser::fromStateString($state, RepoManager::getVersion()));
			if ($newState !== null) {
				$state = BlockParser::toStateString($newState);
			}
		} catch (Throwable $exception) {
			EditThread::getInstance()->debug("Found malformed tile " . $tile->getString(Tile::TAG_ID) . ": " . $exception->getMessage());
		}
		return true;
	}

	/**
	 * @param string $name
	 * @return CompoundTag|null
	 */
	public static function preprocessTileState(string $name): ?CompoundTag
	{
		$state = BlockParser::fromStateString($name, RepoManager::getVersion());
		if (isset(self::$convertors[$state->getName()])) {
			return self::$convertors[$state->getName()]->preprocessTileState($state)?->setString(self::PREPROCESSED_TYPE, self::$convertors[$state->getName()]::class);
		}
		return null;
	}

	public static function load(): void
	{
		/**
		 * @var TileConvertorPiece $convertor
		 */
		foreach ([
			         new ChestTileConvertor("Chest", "minecraft:chest", "minecraft:trapped_chest"),
			         new ContainerTileConvertor("Dispenser", "minecraft:dispenser"),
			         new ContainerTileConvertor("Dropper", "minecraft:dropper"),
			         new ContainerTileConvertor("Hopper", "minecraft:hopper"),
			         new ContainerTileConvertor("ShulkerBox", "minecraft:shulker_box"), //TODO: facing
			         new SignConvertor("Sign", "minecraft:sign"),
		         ] as $convertor) {
			foreach ($convertor->getIdentifiers() as $identifier) {
				self::$convertors[$identifier] = $convertor;
			}
		}
	}
}