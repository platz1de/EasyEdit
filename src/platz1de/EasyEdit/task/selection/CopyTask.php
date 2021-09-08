<?php

namespace platz1de\EasyEdit\task\selection;

use Closure;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\ClipBoardManager;
use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\task\EditTask;
use platz1de\EasyEdit\task\EditTaskHandler;
use platz1de\EasyEdit\task\EditTaskResult;
use platz1de\EasyEdit\task\queued\QueuedEditTask;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\TaskCache;
use platz1de\EasyEdit\worker\WorkerAdapter;
use pocketmine\math\Vector3;
use pocketmine\world\Position;

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
			$finish = static function (EditTaskResult $result): void {
				/** @var DynamicBlockListSelection $copied */
				$copied = $result->getUndo();
				ClipBoardManager::setForPlayer($copied->getPlayer(), $copied);
			};
		}
		WorkerAdapter::queue(new QueuedEditTask($selection, new Pattern([]), $place, self::class, new AdditionalDataManager(), $selection->getPos1()->multiply(-1), $finish));
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "copy";
	}

	/**
	 * @param EditTaskHandler       $handler
	 * @param Selection             $selection
	 * @param Pattern               $pattern
	 * @param Vector3               $place
	 * @param AdditionalDataManager $data
	 */
	public function execute(EditTaskHandler $handler, Selection $selection, Pattern $pattern, Vector3 $place, AdditionalDataManager $data): void
	{
		$full = TaskCache::getFullSelection();
		$selection->useOnBlocks($place, function (int $x, int $y, int $z) use ($handler, $full): void {
			$handler->addToUndo($x, $y, $z, $full->getPos1()->multiply(-1));
		});
	}

	/**
	 * @param Selection             $selection
	 * @param Vector3               $place
	 * @param string                $world
	 * @param AdditionalDataManager $data
	 * @return DynamicBlockListSelection
	 */
	public function getUndoBlockList(Selection $selection, Vector3 $place, string $world, AdditionalDataManager $data): BlockListSelection
	{
		return new DynamicBlockListSelection($selection->getPlayer(), $place, $selection->getCubicStart(), $selection->getCubicEnd());
	}

	/**
	 * @param Selection             $selection
	 * @param float                 $time
	 * @param string                $changed
	 * @param AdditionalDataManager $data
	 */
	public function notifyUser(Selection $selection, float $time, string $changed, AdditionalDataManager $data): void
	{
		Messages::send($selection->getPlayer(), "blocks-copied", ["{time}" => (string) $time, "{changed}" => $changed]);
	}
}