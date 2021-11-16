<?php

namespace platz1de\EasyEdit\task;

use platz1de\EasyEdit\selection\StaticBlockListSelection;
use platz1de\EasyEdit\task\editing\EditTask;
use platz1de\EasyEdit\task\editing\EditTaskResultCache;
use platz1de\EasyEdit\task\editing\selection\StaticPasteTask;
use platz1de\EasyEdit\thread\input\TaskInputData;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\thread\output\HistoryCacheData;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\MixedUtils;
use pocketmine\math\Vector3;
use UnexpectedValueException;

class StaticStoredPasteTask extends ExecutableTask
{
	private int $saveId;
	private bool $keep;
	private bool $isUndo;

	/**
	 * @param string $owner
	 * @param int    $saveId
	 * @param bool   $keep
	 * @param bool   $isUndo
	 * @return StaticStoredPasteTask
	 */
	public static function from(string $owner, int $saveId, bool $keep, bool $isUndo = false): StaticStoredPasteTask
	{
		$instance = new self($owner);
		$instance->saveId = $saveId;
		$instance->keep = $keep;
		$instance->isUndo = $isUndo;
		return $instance;
	}

	/**
	 * @param string $owner
	 * @param int    $id
	 * @param bool   $keep
	 * @param bool   $isUndo
	 */
	public static function queue(string $owner, int $id, bool $keep, bool $isUndo = false): void
	{
		TaskInputData::fromTask(self::from($owner, $id, $keep, $isUndo));
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "static_storage_paste";
	}

	public function execute(): void
	{
		$selection = StorageModule::getStored($this->saveId);
		if (!$this->keep) {
			StorageModule::cleanStored($this->saveId);
		}
		if (!$selection instanceof StaticBlockListSelection) {
			throw new UnexpectedValueException("Storage at id " . $this->saveId . " contained " . get_class($selection) . " expected " . StaticBlockListSelection::class);
		}
		$data = new AdditionalDataManager(true, true);
		$undo = $this->isUndo;
		$data->setResultHandler(static function (EditTask $task, int $changeId) use ($undo): void {
			HistoryCacheData::from($task->getOwner(), $changeId, $undo);
			StaticPasteTask::notifyUser($task->getOwner(), (string) round(EditTaskResultCache::getTime(), 2), MixedUtils::humanReadable(EditTaskResultCache::getChanged()), $task->getDataManager());
		});
		StaticPasteTask::from($this->getOwner(), $selection->getWorldName(), $data, $selection, new Vector3(0, 0, 0), new Vector3(0, 0, 0))->execute();
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putInt($this->saveId);
		$stream->putBool($this->keep);
		$stream->putBool($this->isUndo);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->saveId = $stream->getInt();
		$this->keep = $stream->getBool();
		$this->isUndo = $stream->getBool();
	}
}