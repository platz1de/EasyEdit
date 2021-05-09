<?php

namespace platz1de\EasyEdit\utils;

use platz1de\EasyEdit\selection\Selection;

class TaskCache
{
	/**
	 * @var Selection
	 */
	private static $selection;

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
		return self::$selection;
	}

	public static function clear(): void
	{
		self::$selection = null;
	}
}