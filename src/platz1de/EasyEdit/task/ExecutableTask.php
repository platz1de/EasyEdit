<?php

namespace platz1de\EasyEdit\task;

use InvalidArgumentException;
use platz1de\EasyEdit\handler\EditHandler;
use platz1de\EasyEdit\result\TaskResult;
use platz1de\EasyEdit\result\TaskResultPromise;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\thread\output\OutputData;
use platz1de\EasyEdit\thread\ThreadData;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pmmp\thread\Thread;
use Throwable;

/**
 * @template T of TaskResult
 */
abstract class ExecutableTask
{
	private static int $id = 0;
	private int $taskId;
	private int $flags = self::FLAG_NONE;

	private const FLAG_NONE = 0;
	private const FLAG_STORE_SELECTIONS = 1; //Store selections on the edit thread and return an identifier instead of the actual selection
	private const FLAG_WRITE_IN_PLACE = 2; //Write the result in place instead of creating a new selection in storage (requires FLAG_STORE_SELECTIONS)

	public function __construct()
	{
		$this->taskId = Thread::getCurrentThread() instanceof EditThread ? -1 : ++self::$id;
	}

	/**
	 * @return TaskResultPromise<T>
	 */
	public function run(): TaskResultPromise
	{
		if (Thread::getCurrentThread() instanceof EditThread) {
			return $this->runInternal();
		}
		return EditHandler::runTask($this);
	}

	/**
	 * The effective complexity is used to determine whether a task should be executed on the main thread or not.
	 * @return int
	 */
	abstract public function calculateEffectiveComplexity(): int;

	/**
	 * @return bool whether this task can be executed on the main thread
	 */
	abstract public function canExecuteOnMainThread(): bool;

	/**
	 * @return TaskResultPromise<T>
	 * @internal
	 */
	public function runInternal(): TaskResultPromise
	{
		$start = microtime(true);
		/** @phpstan-var TaskResultPromise<T> $promise */
		$promise = new TaskResultPromise();
		try {
			$result = $this->executeInternal();
		} catch (Throwable $throwable) {
			if ($throwable instanceof CancelException) {
				$promise->cancel(ThreadData::getCancelReason());
			} else {
				EditThread::getInstance()->getLogger()->logException($throwable);
				$promise->reject($throwable->getMessage());
			}
			$result = $this->attemptRecovery();
		}
		$result->enrichWithTime(microtime(true) - $start);
		if (($this->flags & self::FLAG_STORE_SELECTIONS) !== 0) {
			$result->storeSelections();
		}
		$promise->resolve($result);
		return $promise;
	}

	/**
	 * @return T
	 * @throws CancelException
	 */
	abstract protected function executeInternal(): TaskResult;

	/**
	 * @return T
	 * @internal Attempt to recover from cancellation or a crash (e.g. saving undo data)
	 */
	abstract public function attemptRecovery(): TaskResult;

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	abstract public function putData(ExtendedBinaryStream $stream): void;

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	abstract public function parseData(ExtendedBinaryStream $stream): void;

	/**
	 * @param OutputData $data
	 */
	public function sendOutputPacket(OutputData $data): void
	{
		if ($this->taskId === -1) {
			throw new InvalidArgumentException("Cannot send output data for executor tasks");
		}
		$data->setTaskId($this->taskId);
		if ($data->checkSend()) {
			EditThread::getInstance()->sendOutput($data);
		}
	}

	/**
	 * @return string
	 */
	public function fastSerialize(): string
	{
		$stream = new ExtendedBinaryStream();
		$stream->putString(igbinary_serialize($this) ?? "");
		$this->putData($stream);
		return $stream->getBuffer();
	}

	/**
	 * @param string $data
	 * @return ExecutableTask<TaskResult>
	 */
	public static function fastDeserialize(string $data): ExecutableTask
	{
		$stream = new ExtendedBinaryStream($data);
		/** @var ExecutableTask<TaskResult> $task */
		$task = igbinary_unserialize($stream->getString());
		$task->parseData($stream);
		return $task;
	}

	/**
	 * @return array{int}
	 */
	public function __serialize(): array
	{
		return [$this->taskId, $this->flags];
	}

	/**
	 * @param array{int, int} $data
	 */
	public function __unserialize(array $data): void
	{
		$this->taskId = $data[0];
		$this->flags = $data[1];
	}

	/**
	 * @return string
	 */
	abstract public function getTaskName(): string;

	/**
	 * @return int
	 */
	public function getTaskId(): int
	{
		return $this->taskId;
	}

	/**
	 * @return self<T>
	 */
	public function storeSelections(): self
	{
		$this->flags |= self::FLAG_STORE_SELECTIONS;
		return $this;
	}

	/**
	 * @return self<T>
	 */
	public function writeInPlace(): self
	{
		$this->flags |= self::FLAG_WRITE_IN_PLACE;
		return $this;
	}

	/**
	 * @return bool
	 */
	protected function shouldWriteInPlace(): bool
	{
		return ($this->flags & self::FLAG_WRITE_IN_PLACE) !== 0 && ($this->flags & self::FLAG_STORE_SELECTIONS) !== 0;
	}
}