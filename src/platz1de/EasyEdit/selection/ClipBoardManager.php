<?php

namespace platz1de\EasyEdit\selection;

use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\thread\input\task\CleanStorageTask;

class ClipBoardManager
{
	/**
	 * @var StoredSelectionIdentifier[]
	 */
	private static array $clipboard = [];

	/**
	 * @param string $player
	 * @return StoredSelectionIdentifier
	 */
	public static function getFromPlayer(string $player): StoredSelectionIdentifier
	{
		return self::$clipboard[$player];
	}

	/**
	 * @param string                    $player
	 * @param StoredSelectionIdentifier $id
	 */
	public static function setForPlayer(string $player, StoredSelectionIdentifier $id): void
	{
		if (isset(self::$clipboard[$player])) {
			CleanStorageTask::from([self::$clipboard[$player]]);
		}
		self::$clipboard[$player] = $id;
	}
}