<?php

namespace platz1de\EasyEdit\thread;

use pocketmine\scheduler\Task;

class EditAdapter extends Task
{
	private static int $id = 0;

	public function onRun(): void
	{
		EditThread::getInstance()->parseOutput();
	}

	/**
	 * @return int
	 */
	public static function getId(): int
	{
		return ++self::$id;
	}
}