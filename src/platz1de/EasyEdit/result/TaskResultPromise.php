<?php

namespace platz1de\EasyEdit\result;

use Closure;
use LogicException;
use platz1de\EasyEdit\session\SessionIdentifier;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

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
	 * @var array<int, Closure(SessionIdentifier): void>
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
	private SessionIdentifier $cancelReason;

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
	 * @param Closure(SessionIdentifier) : void $callback
	 * @return TaskResultPromise<T>
	 */
	public function onCancel(Closure $callback): self
	{
		if ($this->status === self::STATUS_WAITING) {
			$this->cancel[] = $callback;
		} elseif ($this->status === self::STATUS_CANCEL) {
			$callback($this->cancelReason);
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
	public function cancel(SessionIdentifier $reason): void
	{
		$this->status = self::STATUS_CANCEL;
		$this->cancelReason = $reason;
		foreach ($this->cancel as $callback) {
			$callback($reason);
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

	/**
	 * @return string
	 */
	public function getRawPayload(): string
	{
		if ($this->status === self::STATUS_WAITING) {
			throw new LogicException("Task is not finished yet");
		}
		$stream = new ExtendedBinaryStream();
		$stream->putByte($this->status);
		$stream->putString($this->result->fastSerialize());
		switch ($this->status) {
			case self::STATUS_FAIL:
				$stream->putString($this->message);
				break;
			case self::STATUS_CANCEL:
				$stream->putString($this->cancelReason->fastSerialize());
				break;
		}
		return $stream->getBuffer();
	}

	public function applyRawPayload(string $payload): void
	{
		$stream = new ExtendedBinaryStream($payload);
		$this->status = $stream->getByte();
		/** @var T $result */
		$result = TaskResult::fastDeserialize($stream->getString());
		$this->result = $result;
		switch ($this->status) {
			case self::STATUS_FAIL:
				$this->message = $stream->getString();
				foreach ($this->fail as $callback) {
					$callback($this->message);
				}
				$this->fail = [];
				break;
			case self::STATUS_CANCEL:
				$this->cancelReason = SessionIdentifier::fastDeserialize($stream->getString());
				foreach ($this->cancel as $callback) {
					$callback($this->cancelReason);
				}
				$this->cancel = [];
				break;
		}
		foreach ($this->finish as $callback) {
			$callback($result);
		}
		$this->finish = [];
	}
}