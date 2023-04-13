<?php
/**
 * pthreads returns null for undefined properties, so we have to use normal ones
 * @noinspection PhpMissingFieldTypeInspection
 */

namespace platz1de\EasyEdit\thread;

use platz1de\EasyEdit\task\CancelException;
use platz1de\EasyEdit\thread\chunk\ChunkRequestManager;
use platz1de\EasyEdit\thread\input\InputData;
use platz1de\EasyEdit\thread\output\OutputData;
use platz1de\EasyEdit\thread\output\TaskResultData;
use platz1de\EasyEdit\utils\ConfigManager;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\thread\Thread;
use ThreadedLogger;
use Throwable;

class EditThread extends Thread
{
	/**
	 * @var ThreadedLogger
	 */
	private $logger;
	/**
	 * @var ThreadStats
	 */
	private $stats;
	private static EditThread $instance;
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
		$this->stats = ThreadStats::getInstance();
	}


	public function onRun(): void
	{
		gc_enable();

		$this->getLogger()->debug("Started EditThread");

		while (!$this->isKilled) {
			$this->stats->updateMemory();
			try {
				$this->parseInput(); //This can easily throw an exception when cancelling in an unexpected moment
			} catch (Throwable $throwable) {
				$this->logger->logException($throwable);
			}
			$task = ThreadData::getNextTask();
			if ($task === null) {
				$this->synchronized(function (): void {
					if ($this->inputData === "" && !$this->isKilled) {
						$this->wait();
					}
				});
			} else {
				try {
					ThreadData::clear();
					$this->stats->startTask($task);
					$this->debug("Running task " . $task->getTaskName() . ":" . $task->getTaskId());
					$this->sendOutput(new TaskResultData($task->getTaskId(), $task->executeInternal(), true));
				} catch (Throwable $throwable) {
					if ($throwable instanceof CancelException) {
						$this->debug("Task " . $task->getTaskName() . ":" . $task->getTaskId() . " was cancelled");
					} else {
						$this->logger->logException($throwable);
					}
					$this->sendOutput(new TaskResultData($task->getTaskId(), $task->attemptRecovery(), false, $throwable instanceof CancelException ? null : $throwable->getMessage()));
					ChunkRequestManager::clear();
					//throttle a bit to avoid spamming
					$this->synchronized(function (): void {
						if ($this->inputData === "" && !$this->isKilled) {
							$this->wait(10 * 1000 * 1000);
						}
					});
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
	 * @throws CancelException
	 */
	public function checkExecution(): void
	{
		if ($this->isKilled || ThreadData::requiresCancel()) {
			throw new CancelException();
		}
	}

	public function parseInput(): void
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
				$start = microtime(true);
				$data->handle();
				$this->debug("Handled OUT: " . $data::class . " in " . (microtime(true) - $start) . "s");
			}
		}
	}

	/**
	 * @param InputData $data
	 */
	public function sendToThread(InputData $data): void
	{
		$this->stats->preProcessInput($data);
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
		$this->stats->preProcessOutput($data);
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

	/**
	 * @return ThreadStats
	 */
	public function getStats(): ThreadStats
	{
		return $this->stats;
	}
}
