<?php

namespace platz1de\EasyEdit\thread;

use platz1de\EasyEdit\handler\EditHandler;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\task\ExecutableTask;
use platz1de\EasyEdit\thread\input\ChunkInputData;
use platz1de\EasyEdit\thread\input\InputData;
use platz1de\EasyEdit\thread\input\TaskInputData;
use platz1de\EasyEdit\thread\output\ChunkRequestData;
use platz1de\EasyEdit\thread\output\OutputData;
use platz1de\EasyEdit\thread\output\ResultingChunkData;
use platz1de\EasyEdit\thread\output\TaskResultData;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use RuntimeException;
use Thread;
use Threaded;

class ThreadStats extends Threaded
{
	use SingletonTrait;

	private const STATUS_CRASHED = -1;
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
		switch ($data::class) {
			case TaskResultData::class:
				$this->synchronized(function (): void {
					$this->status = self::STATUS_IDLE;
					$this->lastResponse = microtime(true);
					$this->taskName = "";
					$this->taskId = -1;
					$this->progress = 0.0;
				});
				break;
			case ChunkRequestData::class:
				$this->synchronized(function (): void {
					$this->status = self::STATUS_WAITING;
					$this->lastResponse = microtime(true);
				});
				break;
			case ResultingChunkData::class:
				$this->synchronized(function (): void {
					$this->lastResponse = microtime(true);
				});
				break;
		}
		$this->updateMemory();
	}

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

	public function sendStatusMessage(string $player): void
	{
		$time = microtime(true) - $this->lastResponse;

		Messages::send($player, "thread-stats", [
			"{task}" => match ($this->status) {
				self::STATUS_IDLE => "none",
				self::STATUS_RUNNING, self::STATUS_WAITING => $this->taskName . ":" . $this->taskId . ($this->taskId !== -1 ? " by " . EditHandler::getExecutor($this->taskId)->getName() : ""),
				default => "crashed (" . $this->taskName . ":" . $this->taskId . ($this->taskId !== -1 ? " by " . EditHandler::getExecutor($this->taskId)->getName() : "") . ")",
			},
			"{queue}" => (string) $this->queueLength,
			"{status}" => match ($this->status) {
				self::STATUS_IDLE => TextFormat::GREEN . "OK" . TextFormat::RESET,
				self::STATUS_RUNNING => TextFormat::GOLD . "RUNNING" . TextFormat::GRAY . ": " . match (true) {
						$time > 8 => TextFormat::RED,
						$time > 2 => TextFormat::GOLD,
						default => TextFormat::GREEN
					} . round($time * 1000) . "ms" . TextFormat::RESET,
				self::STATUS_WAITING => TextFormat::AQUA . "WAITING" . TextFormat::GRAY . ": " . TextFormat::GREEN . round($time * 1000) . "ms" . TextFormat::RESET,
				default => TextFormat::RED . "CRASHED" . TextFormat::GRAY . ": " . TextFormat::RED . round($time * 1000) . "ms" . TextFormat::RESET
			},
			"{progress}" => $this->status === self::STATUS_IDLE ? "-" : round($this->progress * 100, 2) . "%",
			"{storage}" => (string) $this->storageSize,
			"{mem_current}" => (string) round(($this->currentMemory / 1024) / 1024, 2),
			"{mem_max}" => (string) round(($this->realMemory / 1024) / 1024, 2)
		]);
	}
}