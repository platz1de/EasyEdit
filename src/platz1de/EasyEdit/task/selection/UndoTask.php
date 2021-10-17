<?php

namespace platz1de\EasyEdit\task\selection;

use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\LinkedBlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\selection\StaticBlockListSelection;
use platz1de\EasyEdit\task\EditTask;
use platz1de\EasyEdit\task\EditTaskHandler;
use platz1de\EasyEdit\task\queued\QueuedEditTask;
use platz1de\EasyEdit\task\selection\cubic\CubicStaticUndo;
use platz1de\EasyEdit\task\selection\type\PastingNotifier;
use platz1de\EasyEdit\thread\EditAdapter;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\TaskCache;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class UndoTask extends EditTask
{
	use CubicStaticUndo;
	use PastingNotifier;

	/**
	 * @param int $id
	 */
	public static function queue(int $id): void
	{
		EditAdapter::queue(new QueuedEditTask(new LinkedBlockListSelection("EasyEdit", "", $id), new Pattern([]), "", new Vector3(0, World::Y_MIN, 0), self::class, new AdditionalDataManager(true, true), new Vector3(0, 0, 0)), null);
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
		}, SelectionContext::full());

		/** @var StaticBlockListSelection $total */
		$total = TaskCache::getFullSelection();
		foreach ($total->getTiles() as $tile) {
			$handler->addTile($tile);
		}
	}
}