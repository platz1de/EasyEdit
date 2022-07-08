<?php

namespace platz1de\EasyEdit\task\editing\selection;

use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\session\SessionIdentifier;
use platz1de\EasyEdit\session\SessionManager;
use platz1de\EasyEdit\task\editing\EditTask;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\EditTaskResultCache;
use platz1de\EasyEdit\thread\input\TaskInputData;
use platz1de\EasyEdit\thread\output\ClipboardCacheData;
use platz1de\EasyEdit\thread\output\MessageSendData;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\MixedUtils;
use pocketmine\math\Vector3;

class CopyTask extends SelectionEditTask
{
	/**
	 * @param string                $world
	 * @param AdditionalDataManager $data
	 * @param Selection             $selection
	 * @param Vector3               $position
	 * @param Vector3               $splitOffset
	 * @return CopyTask
	 */
	public static function from(string $world, AdditionalDataManager $data, Selection $selection, Vector3 $position, Vector3 $splitOffset): CopyTask
	{
		$instance = new self($world, $data, $position);
		SelectionEditTask::initSelection($instance, $selection, $splitOffset);
		return $instance;
	}

	/**
	 * @param Selection $selection
	 * @param Vector3   $place
	 */
	public static function queue(Selection $selection, Vector3 $place): void
	{
		TaskInputData::fromTask(SessionManager::get($selection->getPlayer())->getIdentifier(), self::from($selection->getWorldName(), new AdditionalDataManager(false, true), $selection, $place, $selection->getPos1()->multiply(-1)));
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "copy";
	}

	/**
	 * @param SessionIdentifier $executor
	 * @return BlockListSelection
	 */
	public function getUndoBlockList(SessionIdentifier $executor): BlockListSelection
	{
		//TODO: Make this optional
		return DynamicBlockListSelection::fromWorldPositions($executor->getName(), $this->getPosition(), $this->getTotalSelection()->getCubicStart(), $this->getTotalSelection()->getCubicEnd());
	}

	/**
	 * @param SessionIdentifier     $player
	 * @param string                $time
	 * @param string                $changed
	 * @param AdditionalDataManager $data
	 */
	public static function notifyUser(SessionIdentifier $player, string $time, string $changed, AdditionalDataManager $data): void
	{
		MessageSendData::from($player, Messages::replace("blocks-copied", ["{time}" => $time, "{changed}" => $changed]));
	}

	public function executeEdit(EditTaskHandler $handler, SessionIdentifier $executor): void
	{
		if (!$this->getDataManager()->hasResultHandler()) {
			$this->getDataManager()->setResultHandler(static function (EditTask $task, SessionIdentifier $executor, ?StoredSelectionIdentifier $changeId): void {
				ClipboardCacheData::from($executor, $changeId);
				CopyTask::notifyUser($executor, (string) round(EditTaskResultCache::getTime(), 2), MixedUtils::humanReadable(EditTaskResultCache::getChanged()), $task->getDataManager());
			});
		}
		$offset = $this->getTotalSelection()->getPos1()->multiply(-1);
		$ox = $offset->getFloorX();
		$oy = $offset->getFloorY();
		$oz = $offset->getFloorZ();
		$this->getCurrentSelection()->useOnBlocks(function (int $x, int $y, int $z) use ($oz, $oy, $ox, $handler): void {
			$handler->addToUndo($x, $y, $z, $ox, $oy, $oz);
		}, SelectionContext::full(), $this->getTotalSelection());
	}
}