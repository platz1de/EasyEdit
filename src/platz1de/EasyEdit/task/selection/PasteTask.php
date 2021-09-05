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
use platz1de\EasyEdit\utils\SafeSubChunkExplorer;
use platz1de\EasyEdit\utils\TaskCache;
use platz1de\EasyEdit\utils\TileUtils;
use platz1de\EasyEdit\worker\WorkerAdapter;
use pocketmine\block\tile\Tile;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\Position;
use pocketmine\world\World;

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
		WorkerAdapter::queue(new QueuedEditTask($selection, new Pattern([]), $place, self::class, new AdditionalDataManager(["edit" => true]), $place, $finish));
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "paste";
	}

	/**
	 * @param SafeSubChunkExplorer  $iterator
	 * @param CompoundTag[]         $tiles
	 * @param Selection             $selection
	 * @param Pattern               $pattern
	 * @param Vector3               $place
	 * @param BlockListSelection    $toUndo
	 * @param SafeSubChunkExplorer  $origin
	 * @param int                   $changed
	 * @param AdditionalDataManager $data
	 */
	public function execute(SafeSubChunkExplorer $iterator, array &$tiles, Selection $selection, Pattern $pattern, Vector3 $place, BlockListSelection $toUndo, SafeSubChunkExplorer $origin, int &$changed, AdditionalDataManager $data): void
	{
		/** @var DynamicBlockListSelection $selection */
		Selection::validate($selection, DynamicBlockListSelection::class);
		$place = $place->subtractVector($selection->getPoint());
		$selection->useOnBlocks($place, function (int $x, int $y, int $z) use ($iterator, &$tiles, $selection, $place, $toUndo, &$changed): void {
			$ox = $x - $place->getFloorX();
			$oy = $y - $place->getFloorY();
			$oz = $z - $place->getFloorZ();
			$block = $selection->getIterator()->getBlockAt($ox, $oy, $oz);
			if (Selection::processBlock($block)) {
				$toUndo->addBlock($x, $y, $z, $iterator->getBlockAt($x, $y, $z));
				$iterator->getCurrent()->setFullBlock($x & 0x0f, $y & 0x0f, $z & 0x0f, $block);
				$changed++;

				if (isset($tiles[World::blockHash($x, $y, $z)])) {
					$toUndo->addTile($tiles[World::blockHash($x, $y, $z)]);
					unset($tiles[World::blockHash($x, $y, $z)]);
				}
			}
		});

		/** @var DynamicBlockListSelection $total */
		$total = TaskCache::getFullSelection();
		foreach ($total->getTiles() as $tile) {
			$tiles[World::blockHash($tile->getInt(Tile::TAG_X), $tile->getInt(Tile::TAG_Y), $tile->getInt(Tile::TAG_Z))] = TileUtils::offsetCompound($tile, $place);
		}
	}

	/**
	 * @param Selection             $selection
	 * @param Vector3               $place
	 * @param string                $world
	 * @param AdditionalDataManager $data
	 * @return StaticBlockListSelection
	 */
	public function getUndoBlockList(Selection $selection, Vector3 $place, string $world, AdditionalDataManager $data): BlockListSelection
	{
		/** @var DynamicBlockListSelection $selection */
		Selection::validate($selection, DynamicBlockListSelection::class);
		return new StaticBlockListSelection($selection->getPlayer(), $world, $selection->getPos1()->addVector($place)->subtractVector($selection->getPoint()), $selection->getPos2()->addVector($place)->subtractVector($selection->getPoint()));
	}
}