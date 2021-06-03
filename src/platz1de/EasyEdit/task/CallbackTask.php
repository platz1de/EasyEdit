<?php

namespace platz1de\EasyEdit\task;

use Closure;

//TODO: Move normal QueuedTask to a children as this class doesn't need other properties
class CallbackTask extends QueuedTask
{
	/**
	 * @var Closure
	 */
	private $callback;

	public function __construct(Closure $callback)
	{
		$this->callback = $callback;
	}

	public function callback(): void
	{
		$callback = $this->callback;
		$callback();
	}
}