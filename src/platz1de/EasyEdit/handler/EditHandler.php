<?php

namespace platz1de\EasyEdit\handler;

use platz1de\EasyEdit\result\TaskResult;
use platz1de\EasyEdit\result\TaskResultPromise;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\session\SessionIdentifier;
use platz1de\EasyEdit\task\ExecutableTask;
use platz1de\EasyEdit\thread\input\TaskInputData;
use platz1de\EasyEdit\thread\output\result\CancelledTaskResultData;
use platz1de\EasyEdit\thread\output\result\CrashedTaskResultData;
use platz1de\EasyEdit\thread\output\result\TaskResultData;
use platz1de\EasyEdit\thread\output\TaskNotifyData;
use UnexpectedValueException;

class EditHandler
{
	/**
	 * @var TaskResultPromise<TaskResult>[]
	 */
	private static array $promises = [];
	/**
	 * @var Session[]
	 */
	private static array $executors = [];

	/**
	 * @param Session $executor
	 * @param int     $task
	 */
	public static function affiliateTask(Session $executor, int $task): void
	{
		self::$executors[$task] = $executor;
	}

	/**
	 * @template T of TaskResult
	 * @param ExecutableTask<T> $task
	 * @return TaskResultPromise<T>
	 */
	public static function runTask(ExecutableTask $task): TaskResultPromise
	{
		/** @phpstan-var TaskResultPromise<T> $promise */
		$promise = new TaskResultPromise();
		self::$promises[$task->getTaskId()] = $promise;
		TaskInputData::fromTask($task);
		return $promise;
	}

	/**
	 * @param TaskResultData $result
	 */
	public static function callback(TaskResultData $result): void
	{
		if (!isset(self::$promises[$result->getTaskId()])) {
			throw new UnexpectedValueException("Task with id " . $result->getTaskId() . " not found");
		}
		$promise = self::$promises[$result->getTaskId()];
		$executor = self::getExecutor($result->getTaskId());
		unset(self::$promises[$result->getTaskId()], self::$executors[$result->getTaskId()]);
		if ($result instanceof CrashedTaskResultData) {
			$promise->reject($result->getMessage());
		} else if ($result instanceof CancelledTaskResultData) {
			$promise->cancel($result->getReason()->getName() === $executor->getName());
		}
		$promise->resolve($result->getPayload()); //Note that this will always get called, even if the task was cancelled / rejected
	}

	/**
	 * @param TaskNotifyData $result
	 */
	public static function notify(TaskNotifyData $result): void
	{
		if (!isset(self::$promises[$result->getTaskId()])) {
			throw new UnexpectedValueException("Task with id " . $result->getTaskId() . " not found");
		}
		self::$promises[$result->getTaskId()]->notify($result->getPayload());
	}

	/**
	 * @param int $task
	 * @return SessionIdentifier
	 */
	public static function getExecutor(int $task): SessionIdentifier
	{
		if (!isset(self::$executors[$task])) {
			return SessionIdentifier::internal("EasyEdit");
		}
		return self::$executors[$task]->getIdentifier();
	}
}