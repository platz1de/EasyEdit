<?php

namespace platz1de\EasyEdit\task\selection;

use Closure;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\selection\StaticBlockListSelection;
use platz1de\EasyEdit\task\EditTask;
use platz1de\EasyEdit\task\EditTaskHandler;
use platz1de\EasyEdit\task\queued\QueuedEditTask;
use platz1de\EasyEdit\task\selection\type\PastingNotifier;
use platz1de\EasyEdit\thread\EditAdapter;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\TaskCache;
use platz1de\EasyEdit\utils\TileUtils;
use pocketmine\math\Vector3;
use pocketmine\world\Position;

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
		EditAdapter::queue(new QueuedEditTask($selection, new Pattern([]), $place->getWorld()->getFolderName(), $place->asVector3(), self::class, new AdditionalDataManager(true, true), $place), $finish);
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "paste";
	}

	/**
	 * @param EditTaskHandler       $handler
	 * @param Selection             $selection
	 * @param Vector3               $place
	 * @param AdditionalDataManager $data
	 */
	public function execute(EditTaskHandler $handler, Selection $selection, Vector3 $place, AdditionalDataManager $data): void
	{
		/** @var DynamicBlockListSelection $selection */
		Selection::validate($selection, DynamicBlockListSelection::class);
		$place = $place->subtractVector($selection->getPoint());
		$selection->useOnBlocks($place, function (int $x, int $y, int $z) use ($handler, $selection, $place): void {
			$block = $selection->getIterator()->getBlockAt($x - $place->getFloorX(), $y - $place->getFloorY(), $z - $place->getFloorZ());
			if (Selection::processBlock($block)) {
				$handler->changeBlock($x, $y, $z, $block);
			}
		}, SelectionContext::full());

		/** @var DynamicBlockListSelection $total */
		$total = TaskCache::getFullSelection();
		foreach ($total->getTiles() as $tile) {
			$handler->addTile(TileUtils::offsetCompound($tile, $place));
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