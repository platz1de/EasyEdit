<?php

namespace platz1de\EasyEdit\task\editing\selection;

use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\EditTaskResultCache;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\thread\output\session\ClipboardCacheData;
use platz1de\EasyEdit\thread\output\session\MessageSendData;
use platz1de\EasyEdit\utils\MixedUtils;
use pocketmine\math\Vector3;

class CopyTask extends SelectionEditTask
{
	/**
	 * @param string    $world
	 * @param Selection $selection
	 * @param Vector3   $position
	 * @return CopyTask
	 */
	public static function from(string $world, Selection $selection, Vector3 $position): CopyTask
	{
		$instance = new self($world, $position);
		$instance->selection = $selection;
		$instance->splitOffset = $selection->getPos1()->multiply(-1);
		return $instance;
	}

	public function execute(): void
	{
		$handle = $this->useDefaultHandler();
		if (!$handle) {
			parent::execute();
			return;
		}
		$this->executeAssociated($this, false); //this calls this method again, but without the default handler
		$this->sendOutputPacket(new ClipboardCacheData(StorageModule::finishCollecting()));
		self::notifyUser($this->getTaskId(), (string) round(EditTaskResultCache::getTime(), 2), MixedUtils::humanReadable(EditTaskResultCache::getChanged()));
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
	 * @param int    $taskId
	 * @param string $time
	 * @param string $changed
	 */
	public static function notifyUser(int $taskId, string $time, string $changed): void
	{
		EditThread::getInstance()->sendOutput(new MessageSendData($taskId, Messages::replace("blocks-copied", ["{time}" => $time, "{changed}" => $changed])));
	}

	public function executeEdit(EditTaskHandler $handler): void
	{
		$offset = $this->getTotalSelection()->getPos1()->multiply(-1);
		$ox = $offset->getFloorX();
		$oy = $offset->getFloorY();
		$oz = $offset->getFloorZ();
		$this->getCurrentSelection()->useOnBlocks(function (int $x, int $y, int $z) use ($oz, $oy, $ox, $handler): void {
			$handler->addToUndo($x, $y, $z, $ox, $oy, $oz);
		}, SelectionContext::full(), $this->getTotalSelection());
	}
}