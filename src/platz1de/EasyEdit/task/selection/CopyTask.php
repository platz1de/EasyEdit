<?php

namespace platz1de\EasyEdit\task\selection;

use Closure;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\ClipBoardManager;
use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\task\EditTask;
use platz1de\EasyEdit\task\EditTaskHandler;
use platz1de\EasyEdit\task\queued\QueuedEditTask;
use platz1de\EasyEdit\thread\EditAdapter;
use platz1de\EasyEdit\thread\output\TaskResultData;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\TaskCache;
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
			$finish = static function (TaskResultData $result): void {
				ClipBoardManager::setForPlayer($result->getPlayer(), $result->getChangeId());
			};
		}
		EditAdapter::queue(new QueuedEditTask($selection, new Pattern([]), $place->getWorld()->getFolderName(), $place->asVector3(), self::class, new AdditionalDataManager(false, true), $selection->getPos1()->multiply(-1)), $finish);
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
	 * @param Vector3               $place
	 * @param AdditionalDataManager $data
	 */
	public function execute(EditTaskHandler $handler, Selection $selection, Vector3 $place, AdditionalDataManager $data): void
	{
		$full = TaskCache::getFullSelection();
		$selection->useOnBlocks($place, function (int $x, int $y, int $z) use ($handler, $full): void {
			$handler->addToUndo($x, $y, $z, $full->getPos1()->multiply(-1));
		}, SelectionContext::full());
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
	 * @param string                $player
	 * @param float                 $time
	 * @param string                $changed
	 * @param AdditionalDataManager $data
	 */
	public static function notifyUser(string $player, float $time, string $changed, AdditionalDataManager $data): void
	{
		Messages::send($player, "blocks-copied", ["{time}" => (string) $time, "{changed}" => $changed]);
	}
}