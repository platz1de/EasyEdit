<?php

namespace platz1de\EasyEdit\task\selection;

use platz1de\EasyEdit\history\HistoryManager;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\StaticBlockListSelection;
use platz1de\EasyEdit\task\EditTask;
use platz1de\EasyEdit\task\EditTaskHandler;
use platz1de\EasyEdit\task\EditTaskResult;
use platz1de\EasyEdit\task\queued\QueuedEditTask;
use platz1de\EasyEdit\task\selection\cubic\CubicStaticUndo;
use platz1de\EasyEdit\task\selection\type\PastingNotifier;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\TaskCache;
use platz1de\EasyEdit\worker\WorkerAdapter;
use pocketmine\math\Vector3;
use pocketmine\world\Position;
use pocketmine\world\World;

class UndoTask extends EditTask
{
	use CubicStaticUndo;
	use PastingNotifier;

	/**
	 * @param BlockListSelection $selection
	 */
	public static function queue(BlockListSelection $selection): void
	{
		Selection::validate($selection, StaticBlockListSelection::class);
		WorkerAdapter::queue(new QueuedEditTask($selection, new Pattern([]), new Position(0, World::Y_MIN, 0, $selection->getWorld()), self::class, new AdditionalDataManager(["edit" => true]), new Vector3(0, 0, 0), static function (EditTaskResult $result): void {
			/** @var StaticBlockListSelection $redo */
			$redo = $result->getUndo();
			HistoryManager::addToFuture($redo->getPlayer(), $redo);
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
	 * @param EditTaskHandler       $handler
	 * @param Selection             $selection
	 * @param Vector3               $place
	 * @param AdditionalDataManager $data
	 */
	public function execute(EditTaskHandler $handler, Selection $selection, Vector3 $place, AdditionalDataManager $data): void
	{
		/** @var StaticBlockListSelection $selection */
		Selection::validate($selection, StaticBlockListSelection::class);
		$selection->useOnBlocks($place, function (int $x, int $y, int $z) use ($handler, $selection): void {
			$block = $selection->getIterator()->getBlockAt($x, $y, $z);
			if (Selection::processBlock($block)) {
				$handler->changeBlock($x, $y, $z, $block);
			}
		});

		/** @var StaticBlockListSelection $total */
		$total = TaskCache::getFullSelection();
		foreach ($total->getTiles() as $tile) {
			$handler->addTile($tile);
		}
	}
}