<?php

namespace platz1de\EasyEdit\task\selection;

use Closure;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\ClipBoardManager;
use platz1de\EasyEdit\selection\Cube;
use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\task\EditTask;
use platz1de\EasyEdit\task\QueuedTask;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\TileUtils;
use platz1de\EasyEdit\worker\WorkerAdapter;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\utils\SubChunkIteratorManager;
use pocketmine\math\Vector3;

class CopyTask extends EditTask
{
	/**
	 * @param Selection    $selection
	 * @param Position     $place
	 * @param Closure|null $finish
	 */
	public static function queue(Selection $selection, Position $place, ?Closure $finish = null): void
	{
		if ($finish === null) {
			$finish = static function (Selection $selection, Position $place, DynamicBlockListSelection $copy) {
				ClipBoardManager::setForPlayer($selection->getPlayer(), $copy);
			};
		}
		WorkerAdapter::queue(new QueuedTask($selection, new Pattern([], []), $place, self::class, new AdditionalDataManager(), $finish));
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
	 * @param SubChunkIteratorManager $origin
	 * @param int                     $changed
	 * @param AdditionalDataManager   $data
	 */
	public function execute(SubChunkIteratorManager $iterator, array &$tiles, Selection $selection, Pattern $pattern, Vector3 $place, BlockListSelection $toUndo, SubChunkIteratorManager $origin, int &$changed, AdditionalDataManager $data): void
	{
		$selection->useOnBlocks($place, function (int $x, int $y, int $z) use ($iterator, &$tiles, $selection, $toUndo, &$changed): void {
			$iterator->moveTo($x, $y, $z);
			$toUndo->addBlock($x - $selection->getPos1()->getX(), $y - $selection->getPos1()->getY(), $z - $selection->getPos1()->getZ(), $iterator->currentSubChunk->getBlockId($x & 0x0f, $y & 0x0f, $z & 0x0f), $iterator->currentSubChunk->getBlockData($x & 0x0f, $y & 0x0f, $z & 0x0f));
			$changed++;

			if (isset($tiles[Level::blockHash($x, $y, $z)])) {
				$toUndo->addTile(TileUtils::offsetCompound($tiles[Level::blockHash($x, $y, $z)], $selection->getPos1()->multiply(-1)));
			}
		});
	}

	/**
	 * @param Selection             $selection
	 * @param Vector3               $place
	 * @param string                $level
	 * @param AdditionalDataManager $data
	 * @return DynamicBlockListSelection
	 */
	public function getUndoBlockList(Selection $selection, Vector3 $place, string $level, AdditionalDataManager $data): BlockListSelection
	{
		//TODO: Non-cubic selections
		/** @var Cube $selection */
		Selection::validate($selection, Cube::class);
		return new DynamicBlockListSelection($selection->getPlayer(), $place->subtract($selection->getPos1()), $selection->getPos2()->getX() - $selection->getPos1()->getX(), $selection->getPos2()->getY() - $selection->getPos1()->getY(), $selection->getPos2()->getZ() - $selection->getPos1()->getZ());
	}

	/**
	 * @param Selection $selection
	 * @param float     $time
	 * @param int       $changed
	 */
	public function notifyUser(Selection $selection, float $time, int $changed): void
	{
		Messages::send($selection->getPlayer(), "blocks-copied", ["{time}" => $time, "{changed}" => $changed]);
	}
}