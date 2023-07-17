<?php

namespace platz1de\EasyEdit\thread;

use platz1de\EasyEdit\session\SessionIdentifier;
use platz1de\EasyEdit\task\CancelException;
use platz1de\EasyEdit\thread\chunk\ChunkRequestManager;
use platz1de\EasyEdit\thread\input\InputData;
use platz1de\EasyEdit\thread\output\OutputData;
use platz1de\EasyEdit\thread\output\TaskResultData;
use platz1de\EasyEdit\utils\ConfigManager;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\thread\log\ThreadSafeLogger;
use pocketmine\thread\Thread;
use Throwable;

class EditThread extends Thread
{
	/**
	 * @var ThreadSafeLogger
	 */
	private ThreadSafeLogger $logger;
	/**
	 * @var ThreadStats
	 */
	private ThreadStats $stats;
	private static EditThread $instance;

	/**
	 * Note: pthreads handling of strings is a bit weird
	 * Properties for some reason just stop working when main thread accesses them before the thread is ready
	 * As this only applies to strings, we just unset them whenever they were accessed
	 */
	private string $inputData;
	private string $outputData;

	/**
	 * EditThread constructor.
	 * @param ThreadSafeLogger $logger
	 */
	public function __construct(ThreadSafeLogger $logger)
	{
		self::$instance = $this;
		$this->logger = $logger;
		$this->stats = ThreadStats::getInstance();
	}


	public function onRun(): void
	{
		gc_enable();

		TypeConverter::getInstance(); //TODO: Remove "fast" setting when we finally can execute this in the main thread

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
					if (!isset($this->inputData) && !$this->isKilled) {
						$this->wait();
					}
				});
			} else {
				ThreadData::clear();
				$this->stats->startTask($task);
				$this->debug("Running task " . $task->getTaskName() . ":" . $task->getTaskId());
				$this->sendOutput(new TaskResultData($task->getTaskId(), $task->run()->getRawPayload()));
				ChunkRequestManager::clear();
			}
		}
	}

	public function waitForData(): void
	{
		$this->synchronized(function (): void {
			if (!isset($this->inputData) && !$this->isKilled) {
				$this->wait();
			}
		});
		$this->parseInput();
	}

	/**
	 * @return ThreadSafeLogger
	 */
	public function getLogger(): ThreadSafeLogger
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
		if (isset($this->inputData)) {
			$input = $this->synchronized(function (): string {
				$input = $this->inputData;
				unset($this->inputData);
				return $input;
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
		if (isset($this->outputData)) {
			$output = $this->synchronized(function (): string {
				$output = $this->outputData;
				unset($this->outputData);
				return $output;
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
		$this->synchronized(function (string $add): void {
			$stream = new ExtendedBinaryStream($this->inputData ?? "");
			$stream->putString($add);
			$this->inputData = $stream->getBuffer();

			$this->notify();
		}, $add);
	}

	/**
	 * @param OutputData $data
	 * @internal
	 */
	public function sendOutput(OutputData $data): void
	{
		$this->stats->preProcessOutput($data);
		$add = $data->fastSerialize();
		$this->synchronized(function (string $add): void {
			$stream = new ExtendedBinaryStream($this->outputData ?? "");
			$stream->putString($add);
			$this->outputData = $stream->getBuffer();
		}, $add);
	}

	public function quit(): void
	{
		ThreadData::requirePause(SessionIdentifier::internal("Thread shutdown"));
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
