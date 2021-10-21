<?php

namespace platz1de\EasyEdit\thread\output;

use platz1de\EasyEdit\cache\ClosureCache;
use platz1de\EasyEdit\task\EditTask;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\MixedUtils;
use platz1de\EasyEdit\utils\ReferencedWorldHolder;

class TaskResultData extends OutputData
{
	use ReferencedWorldHolder;

	private string $player;
	/**
	 * @var class-string<EditTask>
	 */
	private string $task;
	private float $time;
	private int $changes;
	private AdditionalDataManager $dataManager;
	private int $changeId;

	/**
	 * @param string                 $player
	 * @param class-string<EditTask> $task
	 * @param float                  $time
	 * @param int                    $changes
	 * @param AdditionalDataManager  $dataManager
	 * @param int                    $changeId
	 */
	public static function from(string $player, string $task, float $time, int $changes, AdditionalDataManager $dataManager, int $changeId): void
	{
		$data = new self();
		$data->player = $player;
		$data->task = $task;
		$data->time = $time;
		$data->changes = $changes;
		$data->dataManager = $dataManager;
		$data->changeId = $changeId;
		$data->send();
	}

	public function handle(): void
	{
		$this->task::notifyUser($this->player, round($this->time, 2), MixedUtils::humanReadable($this->changes), $this->dataManager);
		ClosureCache::execute($this);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->player);
		$stream->putString($this->task);
		$stream->putFloat($this->time);
		$stream->putInt($this->changes);
		$stream->putString(igbinary_serialize($this->dataManager));
		$stream->putInt($this->changeId);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->player = $stream->getString();
		$this->task = $stream->getString();
		$this->time = $stream->getFloat();
		$this->changes = $stream->getInt();
		$this->dataManager = igbinary_unserialize($stream->getString());
		$this->changeId = $stream->getInt();
	}

	/**
	 * @return class-string<EditTask>
	 */
	public function getTask(): string
	{
		return $this->task;
	}

	/**
	 * @return string
	 */
	public function getPlayer(): string
	{
		return $this->player;
	}

	/**
	 * @return int
	 */
	public function getChangeId(): int
	{
		return $this->changeId;
	}

	/**
	 * @return float
	 */
	public function getTime(): float
	{
		return $this->time;
	}

	/**
	 * @return int
	 */
	public function getChanges(): int
	{
		return $this->changes;
	}

	/**
	 * @return AdditionalDataManager
	 */
	public function getDataManager(): AdditionalDataManager
	{
		return $this->dataManager;
	}
}