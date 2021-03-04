<?php

namespace platz1de\EasyEdit\task\selection;

use Closure;
use platz1de\EasyEdit\history\HistoryManager;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\StaticBlockListSelection;
use platz1de\EasyEdit\task\QueuedTask;
use platz1de\EasyEdit\worker\WorkerAdapter;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\utils\SubChunkIteratorManager;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\tile\Tile;

class UndoTask extends PasteTask
{
	/**
	 * @param BlockListSelection $selection
	 * @param Position|null      $place  This argument ... just exists
	 * @param Closure|null       $finish This argument ... just exists
	 * @noinspection PhpUnusedParameterInspection
	 */
	public static function queue(BlockListSelection $selection, ?Position $place = null, ?Closure $finish = null): void
	{
		Selection::validate($selection, StaticBlockListSelection::class);
		WorkerAdapter::queue(new QueuedTask($selection, new Pattern([], []), new Position(0, 0, 0, Server::getInstance()->getDefaultLevel()), self::class, static function (Selection $selection, Position $place, StaticBlockListSelection $redo) {
			HistoryManager::addToFuture($selection->getPlayer(), $redo);
		}));
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "undo";
	}

	/**
	 * @param SubChunkIteratorManager $iterator
	 * @param array                   $tiles
	 * @param Selection               $selection
	 * @param Pattern                 $pattern
	 * @param Vector3                 $place
	 * @param BlockListSelection      $toUndo
	 * @param SubChunkIteratorManager $origin
	 * @param int                     $changed
	 */
	public function execute(SubChunkIteratorManager $iterator, array &$tiles, Selection $selection, Pattern $pattern, Vector3 $place, BlockListSelection $toUndo, SubChunkIteratorManager $origin, int &$changed): void
	{
		/** @var StaticBlockListSelection $selection */
		Selection::validate($selection, StaticBlockListSelection::class);
		$selection->useOnBlocks($place, function (int $x, int $y, int $z) use ($iterator, &$tiles, $selection, $toUndo, &$changed): void {
			$selection->getIterator()->moveTo($x, $y, $z);
			$blockId = $selection->getIterator()->currentSubChunk->getBlockId($x & 0x0f, $y & 0x0f, $z & 0x0f);
			if (Selection::processBlock($blockId)) {
				$iterator->moveTo($x, $y, $z);
				$toUndo->addBlock($x, $y, $z, $iterator->currentSubChunk->getBlockId($x & 0x0f, $y & 0x0f, $z & 0x0f), $iterator->currentSubChunk->getBlockData($x & 0x0f, $y & 0x0f, $z & 0x0f));
				$iterator->currentSubChunk->setBlock($x & 0x0f, $y & 0x0f, $z & 0x0f, $blockId, $selection->getIterator()->currentSubChunk->getBlockData($x & 0x0f, $y & 0x0f, $z & 0x0f));
				$changed++;

				if (isset($tiles[Level::blockHash($x, $y, $z)])) {
					$toUndo->addTile($tiles[Level::blockHash($x, $y, $z)]);
					unset($tiles[Level::blockHash($x, $y, $z)]);
				}
			}
		});

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