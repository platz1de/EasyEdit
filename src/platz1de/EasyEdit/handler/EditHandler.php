<?php

namespace platz1de\EasyEdit\handler;

use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\ExecutableTask;
use platz1de\EasyEdit\thread\input\TaskInputData;
use platz1de\EasyEdit\thread\output\session\SessionOutputData;
use platz1de\EasyEdit\thread\output\TaskResultData;
use pocketmine\promise\Promise;
use pocketmine\promise\PromiseResolver;
use UnexpectedValueException;

class EditHandler
{
	/**
	 * @var PromiseResolver<TaskResultData>[]
	 */
	private static array $promises = [];
	/**
	 * @var Session[]
	 */
	private static array $executors = [];

	/**
	 * @param Session        $executor
	 * @param ExecutableTask $task
	 * @return Promise<TaskResultData>
	 */
	public static function runPlayerTask(Session $executor, ExecutableTask $task): Promise
	{
		self::$executors[$task->getTaskId()] = $executor;
		return self::runTask($task);
	}

	/**
	 * @param ExecutableTask $task
	 * @return Promise<TaskResultData>
	 */
	public static function runTask(ExecutableTask $task): Promise
	{
		$promise = new PromiseResolver();
		self::$promises[$task->getTaskId()] = $promise;
		TaskInputData::fromTask($task);
		return $promise->getPromise();
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
		unset(self::$promises[$result->getTaskId()], self::$executors[$result->getTaskId()]);
		$promise->resolve($result);
	}

	/**
	 * @param SessionOutputData $data
	 */
	public static function processSessionOutput(SessionOutputData $data): void
	{
		if (!isset(self::$executors[$data->getTaskId()])) {
			EasyEdit::getInstance()->getLogger()->error("Received output for invalid task " . $data->getTaskId());
			return;
		}
		$data->handleSession(self::$executors[$data->getTaskId()]);
	}
}