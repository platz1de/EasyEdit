<?php

namespace platz1de\EasyEdit\task\editing\selection;

use _PHPStan_3e014c27f\Symfony\Component\Process\Exception\RuntimeException;
use platz1de\EasyEdit\handler\EditHandler;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\editing\EditTask;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\EditTaskResultCache;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\thread\output\session\ClipboardCacheData;
use platz1de\EasyEdit\thread\output\session\MessageSendData;
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
	 * @return CopyTask
	 */
	public static function from(string $world, AdditionalDataManager $data, Selection $selection, Vector3 $position): CopyTask
	{
		$instance = new self($world, $data, $position);
		$instance->selection = $selection;
		$instance->splitOffset = $selection->getPos1()->multiply(-1);
		return $instance;
	}

	/**
	 * @param Session   $session
	 * @param Selection $selection
	 * @param Vector3   $place
	 */
	public static function queue(Session $session, Selection $selection, Vector3 $place): void
	{
		EditHandler::runPlayerTask($session, self::from($selection->getWorldName(), new AdditionalDataManager(false, true), $selection, $place));
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "copy";
	}

	/**
	 * @return BlockListSelection
	 */
	public function getUndoBlockList(): BlockListSelection
	{
		//TODO: Make this optional
		return DynamicBlockListSelection::fromWorldPositions($this->getPosition(), $this->getTotalSelection()->getCubicStart(), $this->getTotalSelection()->getCubicEnd());
	}

	/**
	 * @param int                   $taskId
	 * @param string                $time
	 * @param string                $changed
	 * @param AdditionalDataManager $data
	 */
	public static function notifyUser(int $taskId, string $time, string $changed, AdditionalDataManager $data): void
	{
		EditThread::getInstance()->sendOutput(new MessageSendData($taskId, Messages::replace("blocks-copied", ["{time}" => $time, "{changed}" => $changed])));
	}

	public function executeEdit(EditTaskHandler $handler): void
	{
		if (!$this->getDataManager()->hasResultHandler()) {
			$this->getDataManager()->setResultHandler(function (EditTask $task, ?StoredSelectionIdentifier $changeId): void {
				if ($changeId === null) {
					throw new RuntimeException("Could not find copied selection");
				}
				$this->sendOutputPacket(new ClipboardCacheData($changeId));
				CopyTask::notifyUser($this->getTaskId(), (string) round(EditTaskResultCache::getTime(), 2), MixedUtils::humanReadable(EditTaskResultCache::getChanged()), $task->getDataManager());
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