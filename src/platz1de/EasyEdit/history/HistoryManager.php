<?php

namespace platz1de\EasyEdit\history;

use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\selection\StaticBlockListSelection;
use platz1de\EasyEdit\task\selection\RedoTask;
use platz1de\EasyEdit\task\selection\UndoTask;

class HistoryManager
{
	/**
	 * //undo
	 * @var StaticBlockListSelection[][]
	 */
	private static $past = [];
	/**
	 * //redo
	 * @var StaticBlockListSelection[][]
	 */
	private static $future = [];

	/**
	 * @param string                   $player
	 * @param StaticBlockListSelection $entry
	 */
	public static function addToHistory(string $player, StaticBlockListSelection $entry): void
	{
		self::$past[$player][] = $entry;
		//other timeline
		self::$future[$player] = [];
		if (count(self::$past) > 100) {
			array_shift(self::$past);
			Messages::send($player, "history-full");
		} elseif (count(self::$past) >= 95) {
			Messages::send($player, "history-nearly-full");
		}
	}

	/**
	 * @param string                   $player
	 * @param StaticBlockListSelection $entry
	 */
	public static function addToFuture(string $player, StaticBlockListSelection $entry): void
	{
		self::$future[$player][] = $entry;
	}

	/**
	 * @param string $player
	 * @return bool
	 */
	public static function canUndo(string $player): bool
	{
		return isset(self::$past[$player]) && count(self::$past[$player]) > 0;
	}

	/**
	 * @param string $player
	 * @return bool
	 */
	public static function canRedo(string $player): bool
	{
		return isset(self::$future[$player]) && count(self::$future[$player]) > 0;
	}

	/**
	 * @param string $player
	 */
	public static function undoStep(string $player): void
	{
		if (self::canUndo($player)) {
			$undo = array_pop(self::$past[$player]);

			UndoTask::queue($undo);
		}
	}

	/**
	 * @param string $player
	 */
	public static function redoStep(string $player): void
	{
		if (self::canRedo($player)) {
			$redo = array_pop(self::$future[$player]);

			RedoTask::queue($redo);
		}
	}
}