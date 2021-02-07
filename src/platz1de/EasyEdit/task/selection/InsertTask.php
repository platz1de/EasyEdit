<?php

namespace platz1de\EasyEdit\task\selection;

use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\utils\TileUtils;
use pocketmine\level\Level;
use pocketmine\level\utils\SubChunkIteratorManager;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\tile\Tile;

class InsertTask extends PasteTask
{
	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "insert";
	}

	/**
	 * @param SubChunkIteratorManager $iterator
	 * @param CompoundTag[]           $tiles
	 * @param Selection               $selection
	 * @param Pattern                 $pattern
	 * @param Vector3                 $place
	 * @param BlockListSelection      $toUndo
	 * @param SubChunkIteratorManager $origin
	 */
	public function execute(SubChunkIteratorManager $iterator, array &$tiles, Selection $selection, Pattern $pattern, Vector3 $place, BlockListSelection $toUndo, SubChunkIteratorManager $origin): void
	{
		/** @var DynamicBlockListSelection $selection */
		Selection::validate($selection, DynamicBlockListSelection::class);
		$place = $place->subtract($selection->getPoint());
		foreach ($selection->getAffectedBlocks($place) as $block) {
			$selection->getIterator()->moveTo($block->getX(), $block->getY(), $block->getZ());
			$blockId = $selection->getIterator()->currentSubChunk->getBlockId($block->getX() & 0x0f, $block->getY() & 0x0f, $block->getZ() & 0x0f);
			if (Selection::processBlock($blockId)) {
				$iterator->moveTo($block->getX() + $place->getX(), $block->getY() + $place->getY(), $block->getZ() + $place->getZ());
				if ($iterator->currentSubChunk->getBlockId($block->getX() + $place->getX() & 0x0f, $block->getY() + $place->getY() & 0x0f, $block->getZ() + $place->getZ() & 0x0f) === 0) {
					$toUndo->addBlock($block->getX() + $place->getX(), $block->getY() + $place->getY(), $block->getZ() + $place->getZ(), $iterator->currentSubChunk->getBlockId($block->getX() + $place->getX() & 0x0f, $block->getY() + $place->getY() & 0x0f, $block->getZ() + $place->getZ() & 0x0f), $iterator->currentSubChunk->getBlockData($block->getX() + $place->getX() & 0x0f, $block->getY() + $place->getY() & 0x0f, $block->getZ() + $place->getZ() & 0x0f));
					$iterator->currentSubChunk->setBlock(($block->getX() + $place->getX()) & 0x0f, ($block->getY() + $place->getY()) & 0x0f, ($block->getZ() + $place->getZ()) & 0x0f, $blockId, $selection->getIterator()->currentSubChunk->getBlockData($block->getX() & 0x0f, $block->getY() & 0x0f, $block->getZ() & 0x0f));

					if (isset($tiles[Level::blockHash($block->getX() + $place->getX(), $block->getY() + $place->getY(), $block->getZ() + $place->getZ())])) {
						$toUndo->addTile($tiles[Level::blockHash($block->getX() + $place->getX(), $block->getY() + $place->getY(), $block->getZ() + $place->getZ())]);
						unset($tiles[Level::blockHash($block->getX() + $place->getX(), $block->getY() + $place->getY(), $block->getZ() + $place->getZ())]);
					}
				}
			}
		}

		foreach ($selection->getTiles() as $tile) {
			$compoundTag = TileUtils::offsetCompound($tile, $place);
			$tiles[Level::blockHash($compoundTag->getInt(Tile::TAG_X), $compoundTag->getInt(Tile::TAG_Y), $compoundTag->getInt(Tile::TAG_Z))] = $compoundTag;
		}
	}
}