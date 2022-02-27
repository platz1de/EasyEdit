<?php

namespace platz1de\EasyEdit\cache;

use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\task\StaticStoredPasteTask;
use platz1de\EasyEdit\thread\input\task\CleanStorageTask;

class HistoryCache
{
	/**
	 * //undo
	 * @var StoredSelectionIdentifier[][]
	 */
	private static array $pastCache = [];
	/**
	 * //redo
	 * @var StoredSelectionIdentifier[][]
	 */
	private static array $futureCache = [];

	/**
	 * @param string                    $player
	 * @param StoredSelectionIdentifier $id
	 * @param bool                      $fromUndo
	 * @return void
	 */
	public static function addToCache(string $player, StoredSelectionIdentifier $id, bool $fromUndo): void
	{
		if ($fromUndo) {
			self::$futureCache[$player][] = $id;
		} else {
			self::$pastCache[$player][] = $id;
			if (isset(self::$futureCache[$player])) {
				CleanStorageTask::from(self::$futureCache[$player]);
				self::$futureCache[$player] = [];
			}
		}
	}

	/**
	 * @param string $player
	 * @return bool
	 */
	public static function canUndo(string $player): bool
	{
		return isset(self::$pastCache[$player]) && count(self::$pastCache[$player]) > 0;
	}

	/**
	 * @param string $player
	 * @return bool
	 */
	public static function canRedo(string $player): bool
	{
		return isset(self::$futureCache[$player]) && count(self::$futureCache[$player]) > 0;
	}

	/**
	 * @param string $target
	 * @param string $player
	 */
	public static function undoStep(string $target, string $player): void
	{
		if (self::canUndo($target)) {
			$undo = array_pop(self::$pastCache[$target]);

			if ($undo !== null) {
				StaticStoredPasteTask::queue($player, $undo, false, true);
			}
		}
	}

	/**
	 * @param string $target
	 * @param string $player
	 */
	public static function redoStep(string $target, string $player): void
	{
		if (self::canRedo($target)) {
			$redo = array_pop(self::$futureCache[$target]);

			if ($redo !== null) {
				StaticStoredPasteTask::queue($player, $redo, false);
			}
		}
	}
}