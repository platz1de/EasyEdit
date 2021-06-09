<?php

namespace platz1de\EasyEdit\task\queued;

interface QueuedTask
{
	/**
	 * @return bool
	 */
	public function isInstant(): bool;

	public function execute(): void;

	/**
	 * @return bool whether the task is done
	 */
	public function continue(): bool;
}