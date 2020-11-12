<?php

namespace platz1de\EasyEdit\task\selection;

use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\Cube;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\StaticBlockListSelection;
use platz1de\EasyEdit\task\EditTask;
use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\level\utils\SubChunkIteratorManager;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;

class SetTask extends EditTask
{
	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "set";
	}

	/**
	 * @param SubChunkIteratorManager  $iterator
	 * @param CompoundTag[]            $tiles
	 * @param Selection                $selection
	 * @param Pattern                  $pattern
	 * @param Vector3                  $place
	 * @param StaticBlockListSelection $toUndo
	 */
	public function execute(SubChunkIteratorManager $iterator, array &$tiles, Selection $selection, Pattern $pattern, Vector3 $place, StaticBlockListSelection $toUndo): void
	{
		/** @var Cube $selection */
		foreach ($selection->getAffectedBlocks() as $block) {
			$b = $pattern->getFor($block->getX(), $block->getY(), $block->getZ());
			if ($b instanceof Block) {
				$iterator->moveTo($block->getX(), $block->getY(), $block->getZ());
				$toUndo->addBlock($block->getX(), $block->getY(), $block->getZ(), $iterator->currentSubChunk->getBlockId($block->getX() & 0x0f, $block->getY() & 0x0f, $block->getZ() & 0x0f), $iterator->currentSubChunk->getBlockData($block->getX() & 0x0f, $block->getY() & 0x0f, $block->getZ() & 0x0f));
				$iterator->currentSubChunk->setBlock($block->getX() & 0x0f, $block->getY() & 0x0f, $block->getZ() & 0x0f, $b->getId(), $b->getDamage());
				unset($tiles[Level::blockHash($block->getX(), $block->getY(), $block->getZ())]);
			}
		}
	}

	/**
	 * @param Selection $selection
	 * @param Vector3   $place
	 * @param string    $level
	 * @return StaticBlockListSelection
	 */
	public function getUndoBlockList(Selection $selection, Vector3 $place, string $level): StaticBlockListSelection
	{
		/** @var Cube $selection */
		Selection::validate($selection, Cube::class);
		return new StaticBlockListSelection($selection->getPlayer(), $level, $selection->getPos1(), $selection->getPos2()->getX() - $selection->getPos1()->getX(), $selection->getPos2()->getY() - $selection->getPos1()->getY(), $selection->getPos2()->getZ() - $selection->getPos1()->getZ());
	}
}