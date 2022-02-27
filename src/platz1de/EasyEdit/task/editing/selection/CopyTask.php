<?php

namespace platz1de\EasyEdit\task\editing\selection;

use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
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
	 * @param string                $owner
	 * @param string                $world
	 * @param AdditionalDataManager $data
	 * @param Selection             $selection
	 * @param Vector3               $position
	 * @param Vector3               $splitOffset
	 * @return CopyTask
	 */
	public static function from(string $owner, string $world, AdditionalDataManager $data, Selection $selection, Vector3 $position, Vector3 $splitOffset): CopyTask
	{
		$instance = new self($owner);
		SelectionEditTask::initSelection($instance, $world, $data, $selection, $position, $splitOffset);
		return $instance;
	}

	/**
	 * @param Selection $selection
	 * @param Vector3   $place
	 */
	public static function queue(Selection $selection, Vector3 $place): void
	{
		TaskInputData::fromTask(self::from($selection->getPlayer(), $selection->getWorldName(), new AdditionalDataManager(false, true), $selection, $place, $selection->getPos1()->multiply(-1)));
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "copy";
	}

	/**
	 * @return DynamicBlockListSelection
	 */
	public function getUndoBlockList(): BlockListSelection
	{
		//TODO: Make this optional
		return new DynamicBlockListSelection($this->getOwner(), $this->getPosition(), $this->getTotalSelection()->getCubicStart(), $this->getTotalSelection()->getCubicEnd());
	}

	/**
	 * @param string                $player
	 * @param string                $time
	 * @param string                $changed
	 * @param AdditionalDataManager $data
	 */
	public static function notifyUser(string $player, string $time, string $changed, AdditionalDataManager $data): void
	{
		MessageSendData::from($player, Messages::replace("blocks-copied", ["{time}" => $time, "{changed}" => $changed]));
	}

	public function executeEdit(EditTaskHandler $handler): void
	{
		if (!$this->getDataManager()->hasResultHandler()) {
			$this->getDataManager()->setResultHandler(static function (EditTask $task, ?StoredSelectionIdentifier $changeId): void {
				ClipboardCacheData::from($task->getOwner(), $changeId);
				CopyTask::notifyUser($task->getOwner(), (string) round(EditTaskResultCache::getTime(), 2), MixedUtils::humanReadable(EditTaskResultCache::getChanged()), $task->getDataManager());
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