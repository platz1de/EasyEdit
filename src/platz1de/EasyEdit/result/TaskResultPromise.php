<?php

namespace platz1de\EasyEdit\result;

use Closure;

/**
 * @template T of TaskResult
 */
class TaskResultPromise
{
	/**
	 * @var array<int, Closure(T): void>
	 */
	private array $finish = [];
	/**
	 * @var array<int, Closure(bool): void>
	 */
	private array $cancel = [];
	/**
	 * @var array<int, Closure(string): void>
	 */
	private array $fail = [];
	/**
	 * @var array<int, Closure(int): void>
	 */
	private array $notify = [];

	public const STATUS_WAITING = 0;
	public const STATUS_SUCCESS = 1;
	public const STATUS_CANCEL = 2;
	public const STATUS_FAIL = 3;
	private int $status = self::STATUS_WAITING;
	/**
	 * @var T
	 */
	private TaskResult $result;
	private string $message;
	private bool $cancelSelf;
	private float $startTime;

	public function __construct()
	{
		$this->startTime = microtime(true);
	}

	/**
	 * Called whenever the task is finished (successfully or not, data might be empty)
	 * @param Closure(T) : void $callback
	 * @return TaskResultPromise<T>
	 */
	public function then(Closure $callback): self
	{
		if ($this->status === self::STATUS_WAITING) {
			$this->finish[] = $callback;
		} else {
			$callback($this->result);
		}
		return $this;
	}

	/**
	 * Called whenever the task is cancelled
	 * @param Closure(bool) : void $callback
	 * @return TaskResultPromise<T>
	 */
	public function onCancel(Closure $callback): self
	{
		if ($this->status === self::STATUS_WAITING) {
			$this->cancel[] = $callback;
		} elseif ($this->status === self::STATUS_CANCEL) {
			$callback($this->cancelSelf);
		}
		return $this;
	}

	/**
	 * Called whenever the task fails (crash / prerequisites not met e.g. world not loaded)
	 * @param Closure(string) : void $callback
	 * @return TaskResultPromise<T>
	 */
	public function onFail(Closure $callback): self
	{
		if ($this->status === self::STATUS_WAITING) {
			$this->fail[] = $callback;
		} elseif ($this->status === self::STATUS_FAIL) {
			$callback($this->message);
		}
		return $this;
	}

	/**
	 * @param Closure(int) : void $callback
	 * @return $this
	 */
	public function update(Closure $callback): self
	{
		if ($this->status === self::STATUS_WAITING) {
			$this->notify[] = $callback;
		}
		return $this;
	}

	/**
	 * @phpstan-param T $result
	 * @internal
	 */
	public function resolve(TaskResult $result): void
	{
		$result->enrichWithTime(microtime(true) - $this->startTime);
		$this->status = self::STATUS_SUCCESS;
		$this->result = $result;
		foreach ($this->finish as $callback) {
			$callback($result);
		}
		$this->finish = [];
	}

	/**
	 * @param string $message
	 * @internal
	 */
	public function reject(string $message): void
	{
		$this->status = self::STATUS_FAIL;
		$this->message = $message;
		foreach ($this->fail as $callback) {
			$callback($message);
		}
		$this->fail = [];
	}

	/**
	 * @internal
	 */
	public function cancel(bool $isSelf): void
	{
		$this->status = self::STATUS_CANCEL;
		$this->cancelSelf = $isSelf;
		foreach ($this->cancel as $callback) {
			$callback($isSelf);
		}
		$this->cancel = [];
	}

	/**
	 * @param int $progress
	 * @internal
	 */
	public function notify(int $progress): void
	{
		foreach ($this->notify as $callback) {
			$callback($progress);
		}
	}
}