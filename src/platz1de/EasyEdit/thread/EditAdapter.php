<?php

namespace platz1de\EasyEdit\thread;

use platz1de\EasyEdit\thread\output\OutputData;
use pocketmine\scheduler\Task;
use Thread;

class EditAdapter extends Task
{
	/**
	 * @var OutputData[]
	 */
	private static array $waiting = [];

	public function onRun(): void
	{
		foreach (self::$waiting as $data) {
			$start = microtime(true);
			$data->handle();
			EditThread::getInstance()->debug("Handled delayed OUT: " . $data::class . " in " . (microtime(true) - $start) . "s");
		}
		self::$waiting = [];
		EditThread::getInstance()->parseOutput();
	}

	/**
	 * @param OutputData $data
	 */
	public static function waitForTick(OutputData $data): void
	{
		self::$waiting[] = $data;
	}
}