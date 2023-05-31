<?php

namespace platz1de\EasyEdit\thread;

use platz1de\EasyEdit\handler\EditHandler;
use platz1de\EasyEdit\result\TaskResult;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\ExecutableTask;
use platz1de\EasyEdit\thread\input\ChunkInputData;
use platz1de\EasyEdit\thread\input\InputData;
use platz1de\EasyEdit\thread\input\TaskInputData;
use platz1de\EasyEdit\thread\output\ChunkRequestData;
use platz1de\EasyEdit\thread\output\OutputData;
use platz1de\EasyEdit\thread\output\result\CancelledTaskResultData;
use platz1de\EasyEdit\thread\output\result\FullTaskResultData;
use platz1de\EasyEdit\thread\output\result\TaskResultData;
use pmmp\thread\Thread;
use pmmp\thread\ThreadSafe;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use RuntimeException;

class ThreadStats extends ThreadSafe
{
	use SingletonTrait;

	private const STATUS_IDLE = 0;
	private const STATUS_RUNNING = 1;
	private const STATUS_WAITING = 2;

	private int $status = self::STATUS_IDLE;
	private float $lastResponse = 0.0;
	private string $taskName = "";
	private int $taskId = -1;
	private float $progress = 0.0;
	private int $queueLength = 0;
	private int $storageSize = 0;
	private int $currentMemory = 0;
	private int $realMemory = 0;

	public function preProcessInput(InputData $data): void
	{
		switch ($data::class) {
			case TaskInputData::class:
				$this->synchronized(function (): void {
					$this->queueLength++;
				});
				break;
			case ChunkInputData::class:
				$this->synchronized(function (): void {
					$this->status = self::STATUS_RUNNING;
					$this->lastResponse = microtime(true);
				});
				break;
		}
	}

	public function preProcessOutput(OutputData $data): void
	{
		$newStatus = null;
		if ($data instanceof TaskResultData) {
			$newStatus = self::STATUS_IDLE;
		} elseif ($data instanceof ChunkRequestData) {
			$newStatus = self::STATUS_WAITING;
		}
		if ($newStatus !== null) {
			$this->synchronized(function ($status): void {
				$this->status = $status;
			}, $newStatus);
		}
		$this->updateMemory();
	}

	/**
	 * @param ExecutableTask<TaskResult> $task
	 */
	public function startTask(ExecutableTask $task): void
	{
		$name = $task->getTaskName();
		$id = $task->getTaskId();
		$this->synchronized(function () use ($name, $id): void {
			$this->status = self::STATUS_RUNNING;
			$this->lastResponse = microtime(true);
			$this->taskName = $name;
			$this->taskId = $id;
			$this->progress = 0.0;
			$this->queueLength = ThreadData::getQueueLength();
		});
		$this->updateMemory();
	}

	public function updateProgress(float $progress): void
	{
		$this->synchronized(function () use ($progress): void {
			$this->progress = $progress;
			$this->lastResponse = microtime(true);
		});
		$this->updateMemory();
	}

	public function updateStorage(int $storageSize): void
	{
		$this->synchronized(function () use ($storageSize): void {
			$this->storageSize = $storageSize;
			$this->lastResponse = microtime(true);
		});
		$this->updateMemory();
	}

	public function updateMemory(): void
	{
		if (!Thread::getCurrentThread() instanceof EditThread) {
			throw new RuntimeException("Attempted to update thread memory outside of edit thread");
		}
		$this->synchronized(function (): void {
			$this->currentMemory = memory_get_usage();
			$this->realMemory = memory_get_usage(true);
			$this->lastResponse = microtime(true);
		});
	}

	public function hasTask(): bool
	{
		return $this->status !== self::STATUS_IDLE;
	}

	public function sendStatusMessage(Session $session): void
	{
		$time = microtime(true) - $this->lastResponse;

		$session->sendMessage("thread-stats", [
			"{task}" => $this->hasTask() ? $this->taskName . ":" . $this->taskId . ($this->taskId !== -1 ? " by " . EditHandler::getExecutor($this->taskId)->getName() : "") : "none",
			"{queue}" => (string) $this->queueLength,
			"{status}" => match ($this->status) {
				self::STATUS_IDLE => TextFormat::GREEN . "OK" . TextFormat::RESET,
				self::STATUS_RUNNING => TextFormat::GOLD . "RUNNING" . TextFormat::GRAY . ": " . match (true) {
						$time > 8 => TextFormat::RED,
						$time > 2 => TextFormat::GOLD,
						default => TextFormat::GREEN
					} . round($time * 1000) . "ms" . TextFormat::RESET,
				self::STATUS_WAITING => TextFormat::AQUA . "WAITING" . TextFormat::GRAY . ": " . TextFormat::GREEN . round($time * 1000) . "ms" . TextFormat::RESET,
				default => TextFormat::RED . "UNKNOWN" . TextFormat::RESET
			},
			"{progress}" => $this->status === self::STATUS_IDLE ? "-" : round($this->progress * 100, 2) . "%",
			"{storage}" => (string) $this->storageSize,
			"{mem_current}" => (string) round(($this->currentMemory / 1024) / 1024, 2),
			"{mem_max}" => (string) round(($this->realMemory / 1024) / 1024, 2)
		]);
	}
}