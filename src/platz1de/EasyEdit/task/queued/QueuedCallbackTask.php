<?php

namespace platz1de\EasyEdit\task\queued;

use BadMethodCallException;
use Closure;

class QueuedCallbackTask implements QueuedTask
{
	/**
	 * @var Closure
	 */
	private $callback;

	/**
	 * QueuedCallbackTask constructor.
	 * @param Closure $callback
	 */
	public function __construct(Closure $callback)
	{
		$this->callback = $callback;
	}

	/**
	 * @return bool
	 */
	public function isInstant(): bool
	{
		return true;
	}

	public function execute(): void
	{
		$callback = $this->callback;
		$callback();
	}

	/**
	 * @return bool
	 */
	public function continue(): bool
	{
		throw new BadMethodCallException("Instant tasks can't be continued");
	}
}