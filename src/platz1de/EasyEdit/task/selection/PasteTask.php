<?php

namespace platz1de\EasyEdit\task\selection;

use Closure;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\StaticBlockListSelection;
use platz1de\EasyEdit\task\EditTask;
use platz1de\EasyEdit\task\queued\QueuedEditTask;
use platz1de\EasyEdit\task\selection\type\PastingNotifier;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\SafeSubChunkIteratorManager;
use platz1de\EasyEdit\utils\TileUtils;
use platz1de\EasyEdit\worker\WorkerAdapter;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\tile\Tile;

class PasteTask extends EditTask
{
	use PastingNotifier;

	/**
	 * @param BlockListSelection $selection
	 * @param Position           $place
	 * @param Closure|null       $finish
	 */
	public static function queue(BlockListSelection $selection, Position $place, ?Closure $finish = null): void
	{
		WorkerAdapter::queue(new QueuedEditTask($selection, new Pattern([], []), $place, self::class, new AdditionalDataManager(["edit" => true]), $finish));
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "paste";
	}

	/**
	 * @param SafeSubChunkIteratorManager $iterator
	 * @param CompoundTag[]               $tiles
	 * @param Selection                   $selection
	 * @param Pattern                     $pattern
	 * @param Vector3                     $place
	 * @param BlockListSelection          $toUndo
	 * @param SafeSubChunkIteratorManager $origin
	 * @param int                         $changed
	 * @param AdditionalDataManager       $data
	 */
	public function execute(SafeSubChunkIteratorManager $iterator, array &$tiles, Selection $selection, Pattern $pattern, Vector3 $place, BlockListSelection $toUndo, SafeSubChunkIteratorManager $origin, int &$changed, AdditionalDataManager $data): void
	{
		/** @var DynamicBlockListSelection $selection */
		Selection::validate($selection, DynamicBlockListSelection::class);
		$place = $place->subtract($selection->getPoint());
		$selection->useOnBlocks($place, function (int $x, int $y, int $z) use ($iterator, &$tiles, $selection, $place, $toUndo, &$changed): void {
			$ox = $x - $place->getFloorX();
			$oy = $y - $place->getFloorY();
			$oz = $z - $place->getFloorZ();
			$selection->getIterator()->moveTo($ox, $oy, $oz);
			$blockId = $selection->getIterator()->getCurrent()->getBlockId($ox & 0x0f, $oy & 0x0f, $oz & 0x0f);
			if (Selection::processBlock($blockId)) {
				$iterator->moveTo($x, $y, $z);
				$toUndo->addBlock($x, $y, $z, $iterator->getCurrent()->getBlockId($x & 0x0f, $y & 0x0f, $z & 0x0f), $iterator->getCurrent()->getBlockData($x & 0x0f, $y & 0x0f, $z & 0x0f));
				$iterator->getCurrent()->setBlock($x & 0x0f, $y & 0x0f, $z & 0x0f, $blockId, $selection->getIterator()->getCurrent()->getBlockData($ox & 0x0f, $oy & 0x0f, $oz & 0x0f));
				$changed++;

				if (isset($tiles[Level::blockHash($x, $y, $z)])) {
					$toUndo->addTile($tiles[Level::blockHash($x, $y, $z)]);
					unset($tiles[Level::blockHash($x, $y, $z)]);
				}
			}
		});

		foreach ($selection->getTiles() as $tile) {
			$compoundTag = TileUtils::offsetCompound($tile, $place);
			$tiles[Level::blockHash($compoundTag->getInt(Tile::TAG_X), $compoundTag->getInt(Tile::TAG_Y), $compoundTag->getInt(Tile::TAG_Z))] = $compoundTag;
		}
	}

	/**
	 * @param Selection             $selection
	 * @param Vector3               $place
	 * @param string                $level
	 * @param AdditionalDataManager $data
	 * @return StaticBlockListSelection
	 */
	public function getUndoBlockList(Selection $selection, Vector3 $place, string $level, AdditionalDataManager $data): BlockListSelection
	{
		/** @var DynamicBlockListSelection $selection */
		Selection::validate($selection, DynamicBlockListSelection::class);
		return new StaticBlockListSelection($selection->getPlayer(), $level, $place->subtract($selection->getPoint()), $place->subtract($selection->getPoint())->add($selection->getRealSize()));
	}
}