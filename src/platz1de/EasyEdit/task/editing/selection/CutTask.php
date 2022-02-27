<?php

namespace platz1de\EasyEdit\task\editing\selection;

use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\pattern\PatternArgumentData;
use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\task\editing\EditTask;
use platz1de\EasyEdit\task\editing\EditTaskResultCache;
use platz1de\EasyEdit\task\editing\selection\pattern\SetTask;
use platz1de\EasyEdit\task\ExecutableTask;
use platz1de\EasyEdit\thread\input\TaskInputData;
use platz1de\EasyEdit\thread\output\ClipboardCacheData;
use platz1de\EasyEdit\thread\output\HistoryCacheData;
use platz1de\EasyEdit\thread\output\MessageSendData;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\MixedUtils;
use pocketmine\math\Vector3;

class CutTask extends ExecutableTask
{
	private string $world;
	private Selection $selection;
	private Vector3 $position;
	private CopyTask $executor1;
	private SetTask $executor2;

	/**
	 * @param string    $owner
	 * @param string    $world
	 * @param Selection $selection
	 * @param Vector3   $position
	 * @return CutTask
	 */
	public static function from(string $owner, string $world, Selection $selection, Vector3 $position): CutTask
	{
		$instance = new self($owner);
		$instance->world = $world;
		$instance->selection = $selection;
		$instance->position = $position;
		return $instance;
	}

	/**
	 * @param Selection $selection
	 * @param Vector3   $place
	 */
	public static function queue(Selection $selection, Vector3 $place): void
	{
		TaskInputData::fromTask(self::from($selection->getPlayer(), $selection->getWorldName(), $selection, $place));
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
		$copyData = new AdditionalDataManager(false, true);
		$copyData->setResultHandler(static function (EditTask $task, ?StoredSelectionIdentifier $changeId): void {
			ClipboardCacheData::from($task->getOwner(), $changeId);
		});
		$this->executor1 = CopyTask::from($this->getOwner(), $this->world, $copyData, $this->selection, $this->position, $this->selection->getPos1()->multiply(-1));
		$this->executor1->execute();
		$setData = new AdditionalDataManager(true, true);
		$setData->setResultHandler(static function (EditTask $task, ?StoredSelectionIdentifier $changeId): void {
			HistoryCacheData::from($task->getOwner(), $changeId, false);
			CutTask::notifyUser($task->getOwner(), (string) round(EditTaskResultCache::getTime(), 2), MixedUtils::humanReadable(EditTaskResultCache::getChanged()), $task->getDataManager());
		});
		$this->executor2 = SetTask::from($this->getOwner(), $this->world, $setData, $this->selection, $this->position, Vector3::zero(), StaticBlock::from([], PatternArgumentData::create()->setRealBlock(0)));
		$this->executor2->execute();
	}

	/**
	 * @param string                $player
	 * @param string                $time
	 * @param string                $changed
	 * @param AdditionalDataManager $data
	 */
	public static function notifyUser(string $player, string $time, string $changed, AdditionalDataManager $data): void
	{
		MessageSendData::from($player, Messages::replace("blocks-cut", ["{time}" => $time, "{changed}" => $changed]));
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