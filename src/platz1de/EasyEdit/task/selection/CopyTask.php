<?php

namespace platz1de\EasyEdit\task\selection;

use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\Cube;
use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\task\EditTask;
use platz1de\EasyEdit\utils\TileUtils;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\utils\SubChunkIteratorManager;
use pocketmine\math\Vector3;

class CopyTask extends EditTask
{
	/**
	 * CopyTask constructor.
	 * @param Selection $selection
	 * @param Position  $place
	 */
	public function __construct(Selection $selection, Position $place)
	{
		parent::__construct($selection, new Pattern([], []), $place);
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "copy";
	}

	/**
	 * @param SubChunkIteratorManager $iterator
	 * @param array                   $tiles
	 * @param Selection               $selection
	 * @param Pattern                 $pattern
	 * @param Vector3                 $place
	 * @param BlockListSelection      $toUndo
	 * @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection
	 */
	public function execute(SubChunkIteratorManager $iterator, array &$tiles, Selection $selection, Pattern $pattern, Vector3 $place, BlockListSelection $toUndo): void
	{
		/** @var Cube $selection */
		foreach ($selection->getAffectedBlocks() as $block) {
			$iterator->moveTo($block->getX(), $block->getY(), $block->getZ());
			$toUndo->addBlock($block->getX() - $selection->getPos1()->getX(), $block->getY() - $selection->getPos1()->getY(), $block->getZ() - $selection->getPos1()->getZ(), $iterator->currentSubChunk->getBlockId($block->getX() & 0x0f, $block->getY() & 0x0f, $block->getZ() & 0x0f), $iterator->currentSubChunk->getBlockData($block->getX() & 0x0f, $block->getY() & 0x0f, $block->getZ() & 0x0f));

			if (isset($tiles[Level::blockHash($block->getX(), $block->getY(), $block->getZ())])) {
				$toUndo->addTile(TileUtils::offsetCompound($tiles[Level::blockHash($block->getX(), $block->getY(), $block->getZ())], $selection->getPos1()->multiply(-1)));
			}
		}
	}

	/**
	 * @param Selection $selection
	 * @param Vector3   $place
	 * @param string    $level
	 * @return DynamicBlockListSelection
	 */
	public function getUndoBlockList(Selection $selection, Vector3 $place, string $level): BlockListSelection
	{
		/** @var Cube $selection */
		Selection::validate($selection, Cube::class);
		return new DynamicBlockListSelection($selection->getPlayer(), $place->subtract($selection->getPos1()), $selection->getPos2()->getX() - $selection->getPos1()->getX(), $selection->getPos2()->getY() - $selection->getPos1()->getY(), $selection->getPos2()->getZ() - $selection->getPos1()->getZ());
	}
}