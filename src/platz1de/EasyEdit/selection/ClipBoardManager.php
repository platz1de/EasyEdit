<?php

namespace platz1de\EasyEdit\selection;

use BadMethodCallException;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\thread\input\task\CleanStorageTask;

class ClipBoardManager
{
	/**
	 * @var int[]
	 */
	private static array $clipboard = [];

	/**
	 * @param string $player
	 * @return int
	 */
	public static function getFromPlayer(string $player): int
	{
		return self::$clipboard[$player];
	}

	/**
	 * @param string $player
	 * @param int    $id
	 */
	public static function setForPlayer(string $player, int $id): void
	{
		if ($id === -1) {
			throw new BadMethodCallException("Invalid Task Id -1 given");
		}
		if (isset(self::$clipboard[$player])) {
			EditThread::getInstance()->sendToThread(CleanStorageTask::from([self::$clipboard[$player]]));
		}
		self::$clipboard[$player] = $id;
	}
}