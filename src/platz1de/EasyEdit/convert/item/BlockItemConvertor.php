<?php

namespace platz1de\EasyEdit\convert\item;

use platz1de\EasyEdit\convert\BlockStateConvertor;
use platz1de\EasyEdit\convert\TileConvertor;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\utils\BlockParser;
use platz1de\EasyEdit\utils\RepoManager;
use pocketmine\block\tile\Tile;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\data\bedrock\item\SavedItemData;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use Throwable;

class BlockItemConvertor extends ItemConvertorPiece
{
	private const JAVA_BLOCK_TAG = "BlockStateTag";
	private const JAVA_BLOCK_ENTITY_TAG = "BlockEntityTag"; //same as in bedrock currently, but might change in the future, as pmmp does not use the vanilla way

	public function toBedrock(CompoundTag $item, CompoundTag $tag): void
	{
		$block = $tag->getCompoundTag(self::JAVA_BLOCK_TAG);

		$states = [];
		if ($block !== null) {
			$tag->removeTag(self::JAVA_BLOCK_TAG);
			foreach ($block->getValue() as $key => $value) {
				if (!$value instanceof StringTag) {
					EditThread::getInstance()->debug("Invalid item block state: " . get_class($value) . " is not a string");
					continue;
				}
				$states[$key] = BlockParser::tagFromStringValue($value->getValue());
			}
		}

		$data = new BlockStateData($item->getString(SavedItemData::TAG_NAME), $states, RepoManager::getVersion());
		try {
			$data = BlockStateConvertor::javaToBedrock($data, true);
		} catch (Throwable) {
			return; //Probably not even a block
		}

		$item->setTag(SavedItemData::TAG_BLOCK, $data->toNbt());

		//block entity data (if any)
		$blockEntity = $tag->getCompoundTag(self::JAVA_BLOCK_ENTITY_TAG);
		if ($blockEntity !== null) {
			$tag->removeTag(self::JAVA_BLOCK_ENTITY_TAG);

			$tileName = TileConvertor::itemToTileName($item->getString(SavedItemData::TAG_NAME));
			if ($tileName !== null) {
				$blockEntity->setString(Tile::TAG_ID, $tileName); //temporary, so that the tile can be converted

				$pre = TileConvertor::preprocessTileState(BlockParser::toStateString($data));
				TileConvertor::toBedrock($blockEntity, $pre);

				$blockEntity->removeTag(Tile::TAG_ID);
				$tag->setTag(Item::TAG_BLOCK_ENTITY_TAG, $blockEntity);
			}
		}
	}

	public function toJava(CompoundTag $item, CompoundTag $tag): void
	{
		$block = $item->getCompoundTag(SavedItemData::TAG_BLOCK);
		if ($block === null) {
			return;
		}
		$data = BlockStateData::fromNbt($block);
		$data = BlockStateConvertor::bedrockToJava($data);
		if ($data->getName() !== $item->getString("id")) {
			//On bedrock, items can place completely different types, this is not possible on java though
			EditThread::getInstance()->debug("Invalid item block state: " . $data->getName() . " is not " . $item->getString("id"));
			return; //Remove special block data
		}

		$item->removeTag(SavedItemData::TAG_BLOCK);
		$state = BlockParser::toStateString($data);

		//block entity data (if any)
		$blockEntity = $tag->getCompoundTag(Item::TAG_BLOCK_ENTITY_TAG);
		if ($blockEntity !== null) {
			$tag->removeTag(Item::TAG_BLOCK_ENTITY_TAG);

			$tileName = TileConvertor::itemToTileName($item->getString("id"));
			if ($tileName !== null) {
				$blockEntity->setString(Tile::TAG_ID, $tileName); //temporary, so that the tile can be converted

				TileConvertor::toJava($blockEntity, $state);

				$blockEntity->removeTag(Tile::TAG_ID);
				$tag->setTag(self::JAVA_BLOCK_ENTITY_TAG, $blockEntity);
			}
		}

		//write after block entity, so that the block entity can influence the block state (e.g. for chests)
		$data = BlockParser::fromStateString($state, RepoManager::getVersion());
		$compound = new CompoundTag();
		foreach ($data->getStates() as $key => $value) {
			$compound->setString($key, BlockParser::tagToStringValue($value));
		}
		$tag->setTag(self::JAVA_BLOCK_TAG, $compound);
	}
}