<?php

namespace platz1de\EasyEdit\thread;

use platz1de\EasyEdit\thread\output\OutputData;
use pocketmine\scheduler\Task;

class EditAdapter extends Task
{
	private static int $id = 0;

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
	 * @return int
	 */
	public static function getId(): int
	{
		return ++self::$id;
	}

	/**
	 * @param OutputData $data
	 */
	public static function waitForTick(OutputData $data): void
	{
		self::$waiting[] = $data;
	}
}