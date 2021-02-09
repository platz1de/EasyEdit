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
	 * @param int                     $changed
	 */
	public function execute(SubChunkIteratorManager $iterator, array &$tiles, Selection $selection, Pattern $pattern, Vector3 $place, BlockListSelection $toUndo, SubChunkIteratorManager $origin, int &$changed): void
	{
		/** @var DynamicBlockListSelection $selection */
		Selection::validate($selection, DynamicBlockListSelection::class);
		$place = $place->subtract($selection->getPoint());
		$selection->useOnBlocks($place, function (int $x, int $y, int $z) use ($iterator, &$tiles, $selection, $pattern, $place, $toUndo, $origin, &$changed): void{
			$selection->getIterator()->moveTo($x, $y, $z);
			$blockId = $selection->getIterator()->currentSubChunk->getBlockId($x & 0x0f, $y & 0x0f, $z & 0x0f);
			if (Selection::processBlock($blockId)) {
				$iterator->moveTo($x + $place->getX(), $y + $place->getY(), $z + $place->getZ());
				if ($iterator->currentSubChunk->getBlockId($x + $place->getX() & 0x0f, $y + $place->getY() & 0x0f, $z + $place->getZ() & 0x0f) === 0) {
					$toUndo->addBlock($x + $place->getX(), $y + $place->getY(), $z + $place->getZ(), $iterator->currentSubChunk->getBlockId($x + $place->getX() & 0x0f, $y + $place->getY() & 0x0f, $z + $place->getZ() & 0x0f), $iterator->currentSubChunk->getBlockData($x + $place->getX() & 0x0f, $y + $place->getY() & 0x0f, $z + $place->getZ() & 0x0f));
					$iterator->currentSubChunk->setBlock(($x + $place->getX()) & 0x0f, ($y + $place->getY()) & 0x0f, ($z + $place->getZ()) & 0x0f, $blockId, $selection->getIterator()->currentSubChunk->getBlockData($x & 0x0f, $y & 0x0f, $z & 0x0f));
					$changed++;

					if (isset($tiles[Level::blockHash($x + $place->getX(), $y + $place->getY(), $z + $place->getZ())])) {
						$toUndo->addTile($tiles[Level::blockHash($x + $place->getX(), $y + $place->getY(), $z + $place->getZ())]);
						unset($tiles[Level::blockHash($x + $place->getX(), $y + $place->getY(), $z + $place->getZ())]);
					}
				}
			}
		});

		foreach ($selection->getTiles() as $tile) {
			$compoundTag = TileUtils::offsetCompound($tile, $place);
			$tiles[Level::blockHash($compoundTag->getInt(Tile::TAG_X), $compoundTag->getInt(Tile::TAG_Y), $compoundTag->getInt(Tile::TAG_Z))] = $compoundTag;
		}
	}
}