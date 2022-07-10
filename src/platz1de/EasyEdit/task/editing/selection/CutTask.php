<?php

namespace platz1de\EasyEdit\task\editing\selection;

use platz1de\EasyEdit\handler\EditHandler;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\editing\EditTask;
use platz1de\EasyEdit\task\editing\EditTaskResultCache;
use platz1de\EasyEdit\task\editing\selection\pattern\SetTask;
use platz1de\EasyEdit\task\ExecutableTask;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\thread\output\session\ClipboardCacheData;
use platz1de\EasyEdit\thread\output\session\HistoryCacheData;
use platz1de\EasyEdit\thread\output\session\MessageSendData;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\MixedUtils;
use pocketmine\math\Vector3;
use RuntimeException;

class CutTask extends ExecutableTask
{
	private string $world;
	private Selection $selection;
	private Vector3 $position;
	private CopyTask $executor1;
	private SetTask $executor2;

	/**
	 * @param string    $world
	 * @param Selection $selection
	 * @param Vector3   $position
	 * @return CutTask
	 */
	public static function from(string $world, Selection $selection, Vector3 $position): CutTask
	{
		$instance = new self();
		$instance->world = $world;
		$instance->selection = $selection;
		$instance->position = $position;
		return $instance;
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "cut";
	}

	public function execute(): void
	{
		$copyData = new AdditionalDataManager();
		$copyData->setResultHandler(function (EditTask $task, StoredSelectionIdentifier $changeId): void {
			$this->sendOutputPacket(new ClipboardCacheData($changeId));
		});
		$this->executor1 = CopyTask::from($this->world, $copyData, $this->selection, $this->position);
		$this->executor1->executeAssociated($this);
		$setData = new AdditionalDataManager();
		$setData->setResultHandler(function (EditTask $task, StoredSelectionIdentifier $changeId): void {
			$this->sendOutputPacket(new HistoryCacheData($changeId, false));
			CutTask::notifyUser($task->getTaskId(), (string) round(EditTaskResultCache::getTime(), 2), MixedUtils::humanReadable(EditTaskResultCache::getChanged()), $task->getDataManager());
		});
		$this->executor2 = SetTask::from($this->world, $setData, $this->selection, $this->position, new StaticBlock(0));
		$this->executor2->executeAssociated($this);
	}

	/**
	 * @param int                   $taskId
	 * @param string                $time
	 * @param string                $changed
	 * @param AdditionalDataManager $data
	 */
	public static function notifyUser(int $taskId, string $time, string $changed, AdditionalDataManager $data): void
	{
		EditThread::getInstance()->sendOutput(new MessageSendData($taskId, Messages::replace("blocks-cut", ["{time}" => $time, "{changed}" => $changed])));
	}

	public function getProgress(): float
	{
		return ($this->executor1->getProgress() + (isset($this->executor2) ? $this->executor2->getProgress() : 0)) / 2;
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->world);
		$stream->putString($this->selection->fastSerialize());
		$stream->putVector($this->position);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->world = $stream->getString();
		$this->selection = Selection::fastDeserialize($stream->getString());
		$this->position = $stream->getVector();
	}
}