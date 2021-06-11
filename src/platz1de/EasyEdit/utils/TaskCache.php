<?php

namespace platz1de\EasyEdit\utils;

use BadMethodCallException;
use platz1de\EasyEdit\selection\Selection;

class TaskCache
{
	/**
	 * @var Selection|null
	 */
	private static $selection;

	/**
	 * @param Selection $selection
	 */
	public static function init(Selection $selection): void
	{
		if(self::$selection !== null){
			throw new BadMethodCallException("Tried to overwrite cached selection");
		}
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