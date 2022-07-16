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
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\MixedUtils;
use pocketmine\math\Vector3;

class CopyTask extends SelectionEditTask
{
	private Vector3 $position;

	/**
	 * @param Selection $selection
	 * @param Vector3   $position
	 */
	public function __construct(Selection $selection, Vector3 $position)
	{
		$this->position = $position;
		$this->splitOffset = $selection->getPos1()->multiply(-1);
		parent::__construct($selection);
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
		return DynamicBlockListSelection::fromWorldPositions($this->position, $this->getTotalSelection()->getCubicStart(), $this->getTotalSelection()->getCubicEnd());
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

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putVector($this->position);
		parent::putData($stream);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->position = $stream->getVector();
		parent::parseData($stream);
	}
}