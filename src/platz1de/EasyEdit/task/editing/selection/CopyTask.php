<?php

namespace platz1de\EasyEdit\task\editing\selection;

use Closure;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\task\editing\EditTask;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\EditTaskResultCache;
use platz1de\EasyEdit\thread\input\TaskInputData;
use platz1de\EasyEdit\thread\output\ClipboardCacheData;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\MixedUtils;
use pocketmine\math\Vector3;
use pocketmine\world\Position;

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
		SelectionEditTask::initSelection($instance, $owner, $world, $data, $selection, $position, $splitOffset);
		return $instance;
	}

	/**
	 * @param Selection    $selection
	 * @param Position     $place
	 */
	public static function queue(Selection $selection, Position $place): void
	{
		$data = new AdditionalDataManager(false, true);
		$data->setResultHandler(static function (EditTask $task, int $changeId): void {
			ClipboardCacheData::from($task->getOwner(), $changeId);
			CopyTask::notifyUser($task->getOwner(), (string) round(EditTaskResultCache::getTime(), 2), MixedUtils::humanReadable(EditTaskResultCache::getChanged()), $task->getDataManager());
		});
		TaskInputData::fromTask(self::from($selection->getPlayer(), $place->getWorld()->getFolderName(), $data, $selection, $place->asVector3(), $selection->getPos1()->multiply(-1)));
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
		Messages::send($player, "blocks-copied", ["{time}" => $time, "{changed}" => $changed]);
	}

	public function executeEdit(EditTaskHandler $handler): void
	{
		$full = $this->getTotalSelection();
		$this->getCurrentSelection()->useOnBlocks($this->getPosition(), function (int $x, int $y, int $z) use ($handler, $full): void {
			$handler->addToUndo($x, $y, $z, $full->getPos1()->multiply(-1));
		}, SelectionContext::full(), $this->getTotalSelection());
	}
}