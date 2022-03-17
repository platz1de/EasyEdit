<?php
/**
 * pthreads returns null for undefined properties, so we have to use normal ones
 * @noinspection PhpMissingFieldTypeInspection
 */

namespace platz1de\EasyEdit\thread;

use platz1de\EasyEdit\task\editing\EditTaskResultCache;
use platz1de\EasyEdit\thread\input\InputData;
use platz1de\EasyEdit\thread\output\ChunkRequestData;
use platz1de\EasyEdit\thread\output\CrashReportData;
use platz1de\EasyEdit\thread\output\OutputData;
use platz1de\EasyEdit\utils\ConfigManager;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\thread\Thread;
use ThreadedLogger;
use Throwable;

class EditThread extends Thread
{
	public const STATUS_IDLE = 0;
	public const STATUS_PREPARING = 1;
	public const STATUS_RUNNING = 2;
	public const STATUS_CRASHED = 3;

	/**
	 * @var ThreadedLogger
	 */
	private $logger;
	private static EditThread $instance;
	private int $status = self::STATUS_IDLE;
	private float $lastResponse = 0.0;
	private string $inputData = "";
	private string $outputData = "";

	/**
	 * EditThread constructor.
	 * @param ThreadedLogger $logger
	 */
	public function __construct(ThreadedLogger $logger)
	{
		self::$instance = $this;
		$this->logger = $logger;
	}


	public function onRun(): void
	{
		gc_enable();

		$this->getLogger()->debug("Started EditThread");

		$this->lastResponse = microtime(true);

		$sleep = 0;
		while (!$this->isKilled) {
			try {
				$this->parseInput(); //This can easily throw an exception when cancelling in an unexpected moment
			} catch (Throwable $throwable) {
				$this->logger->logException($throwable);
			}
			if ($this->getStatus() !== self::STATUS_CRASHED) {
				$task = ThreadData::getNextTask();
				ThreadData::setTask($task);
				if ($task === null) {
					$this->synchronized(function (): void {
						if ($this->inputData === "" && !$this->isKilled) {
							$this->wait();
						}
					});
				} else {
					try {
						$this->setStatus(self::STATUS_RUNNING);
						ThreadData::canExecute(); //clear pending cancel requests
						EditTaskResultCache::clear();
						$this->debug("Running task " . $task->getTaskName() . ":" . $task->getTaskId());
						$task->execute();
						$this->setStatus(self::STATUS_IDLE);
					} catch (Throwable $throwable) {
						$this->logger->logException($throwable);
						$this->setStatus(self::STATUS_CRASHED);
						$sleep = time() + 9;
						CrashReportData::from($throwable, $task->getOwner());
						ChunkCollector::clear();
					}
				}
			} else {
				$this->synchronized(function (): void {
					if ($this->inputData === "" && !$this->isKilled) {
						$this->wait(10 * 1000 * 1000);
					}
				});
				if ($sleep < time()) {
					$this->setStatus(self::STATUS_IDLE);
				}
			}
		}
	}

	public function waitForData(): void
	{
		$this->synchronized(function (): void {
			if ($this->inputData === "" && !$this->isKilled) {
				$this->wait();
			}
		});
		$this->parseInput();
	}

	/**
	 * @return ThreadedLogger
	 */
	public function getLogger(): ThreadedLogger
	{
		return $this->logger;
	}

	/**
	 * @param string $message
	 * @return void
	 */
	public function debug(string $message): void
	{
		if (ConfigManager::isSendingDebug()) {
			$this->logger->debug($message);
		}
	}

	/**
	 * @return EditThread
	 */
	public static function getInstance(): EditThread
	{
		$thread = self::getCurrentThread();
		if (!$thread instanceof self) {
			return self::$instance;
		}
		return $thread;
	}

	/**
	 * @return string
	 */
	public function getThreadName(): string
	{
		return "EasyEdit editing";
	}

	/**
	 * @return bool
	 */
	public function isRunning(): bool
	{
		return $this->getStatus() === self::STATUS_RUNNING;
	}

	/**
	 * @return bool
	 */
	public function allowsExecution(): bool
	{
		return !$this->isKilled;
	}

	/**
	 * @return int
	 */
	public function getStatus(): int
	{
		return $this->status;
	}

	/**
	 * @param int $status
	 * @internal
	 */
	public function setStatus(int $status): void
	{
		$this->synchronized(function () use ($status): void {
			$this->status = $status;
			$this->lastResponse = microtime(true);
		});
	}

	//TODO: Implement proper callbacks

	/**
	 * @return float
	 */
	public function getLastResponse(): float
	{
		return $this->getStatus() === self::STATUS_IDLE ? microtime(true) : $this->lastResponse;
	}

	private function parseInput(): void
	{
		if ($this->inputData !== "") {
			$input = "";
			$this->synchronized(function () use (&$input): void {
				$input = $this->inputData;
				$this->inputData = "";
			});
			$stream = new ExtendedBinaryStream($input);

			while (!$stream->feof()) {
				$data = InputData::fastDeserialize($stream->getString());
				$this->debug("Received IN: " . $data::class);
				$data->handle();
			}
		}
	}

	public function parseOutput(): void
	{
		if ($this->outputData !== "") {
			$output = "";
			$this->synchronized(function () use (&$output): void {
				$output = $this->outputData;
				$this->outputData = "";
			});
			$stream = new ExtendedBinaryStream($output);

			while (!$stream->feof()) {
				$data = OutputData::fastDeserialize($stream->getString());
				if ($data instanceof ChunkRequestData) {
					EditAdapter::waitForTick($data);
				} else {
					$start = microtime(true);
					$data->handle();
					$this->debug("Handled OUT: " . $data::class . " in " . (microtime(true) - $start) . "s");
				}
			}
		}
	}

	/**
	 * @param InputData $data
	 */
	public function sendToThread(InputData $data): void
	{
		$add = $data->fastSerialize();
		$this->synchronized(function () use ($add): void {
			$stream = new ExtendedBinaryStream($this->inputData);
			$stream->putString($add);
			$this->inputData = $stream->getBuffer();

			$this->notify();
		});
	}

	/**
	 * @param OutputData $data
	 * @internal
	 */
	public function sendOutput(OutputData $data): void
	{
		$add = $data->fastSerialize();
		$this->synchronized(function () use ($add): void {
			$stream = new ExtendedBinaryStream($this->outputData);
			$stream->putString($add);
			$this->outputData = $stream->getBuffer();
		});
	}

	public function quit(): void
	{
		ThreadData::requirePause();
		parent::quit();
	}
}
