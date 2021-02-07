<?php

namespace platz1de\EasyEdit\task\selection;

use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\StaticBlockListSelection;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\utils\SubChunkIteratorManager;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\tile\Tile;

class RedoTask extends PasteTask
{
	/**
	 * RedoTask constructor.
	 * @param StaticBlockListSelection $selection
	 */
	public function __construct(StaticBlockListSelection $selection)
	{
		parent::__construct($selection, new Position(0, 0, 0, Server::getInstance()->getDefaultLevel()));
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "redo";
	}

	/**
	 * @param SubChunkIteratorManager $iterator
	 * @param array                   $tiles
	 * @param Selection               $selection
	 * @param Pattern                 $pattern
	 * @param Vector3                 $place
	 * @param BlockListSelection      $toUndo
	 * @param SubChunkIteratorManager $origin
	 */
	public function execute(SubChunkIteratorManager $iterator, array &$tiles, Selection $selection, Pattern $pattern, Vector3 $place, BlockListSelection $toUndo, SubChunkIteratorManager $origin): void
	{
		/** @var StaticBlockListSelection $selection */
		foreach ($selection->getAffectedBlocks($place) as $block) {
			$selection->getIterator()->moveTo($block->getX(), $block->getY(), $block->getZ());
			$blockId = $selection->getIterator()->currentSubChunk->getBlockId($block->getX() & 0x0f, $block->getY() & 0x0f, $block->getZ() & 0x0f);
			if (Selection::processBlock($blockId)) {
				$iterator->moveTo($block->getX(), $block->getY(), $block->getZ());
				$toUndo->addBlock($block->getX(), $block->getY(), $block->getZ(), $iterator->currentSubChunk->getBlockId($block->getX() & 0x0f, $block->getY() & 0x0f, $block->getZ() & 0x0f), $iterator->currentSubChunk->getBlockData($block->getX() & 0x0f, $block->getY() & 0x0f, $block->getZ() & 0x0f));
				$iterator->currentSubChunk->setBlock($block->getX() & 0x0f, $block->getY() & 0x0f, $block->getZ() & 0x0f, $blockId, $selection->getIterator()->currentSubChunk->getBlockData($block->getX() & 0x0f, $block->getY() & 0x0f, $block->getZ() & 0x0f));

				if (isset($tiles[Level::blockHash($block->getX(), $block->getY(), $block->getZ())])) {
					$toUndo->addTile($tiles[Level::blockHash($block->getX(), $block->getY(), $block->getZ())]);
					unset($tiles[Level::blockHash($block->getX(), $block->getY(), $block->getZ())]);
				}
			}
		}

		foreach ($selection->getTiles() as $tile) {
			$tiles[Level::blockHash($tile->getInt(Tile::TAG_X), $tile->getInt(Tile::TAG_Y), $tile->getInt(Tile::TAG_Z))] = $tile;
		}
	}

	/**
	 * @param Selection $selection
	 * @param Vector3   $place
	 * @param string    $level
	 * @return StaticBlockListSelection
	 */
	public function getUndoBlockList(Selection $selection, Vector3 $place, string $level): BlockListSelection
	{
		/** @var StaticBlockListSelection $selection */
		Selection::validate($selection, StaticBlockListSelection::class);
		return new StaticBlockListSelection($selection->getPlayer(), $level, $place->add($selection->getPos1()), $selection->getPos2()->getX() - $selection->getPos1()->getX(), $selection->getPos2()->getY() - $selection->getPos1()->getY(), $selection->getPos2()->getZ() - $selection->getPos1()->getZ());
	}
}