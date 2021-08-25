<?php

namespace platz1de\EasyEdit\utils;

use BadMethodCallException;
use platz1de\EasyEdit\selection\Selection;

class TaskCache
{
	private static ?Selection $selection = null;

	/**
	 * @param Selection $selection
	 */
	public static function init(Selection $selection): void
	{
		self::$selection = $selection;
	}

	/**
	 * @return Selection
	 */
	public static function getFullSelection(): Selection
	{
		if (self::$selection === null) {
			throw new BadMethodCallException("Task Cache was never init");
		}
		return self::$selection;
	}

	public static function clear(): void
	{
		self::$selection = null;
	}
}