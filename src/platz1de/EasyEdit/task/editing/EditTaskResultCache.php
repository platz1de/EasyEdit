<?php

namespace platz1de\EasyEdit\task\editing;

class EditTaskResultCache
{
	private static float $time = 0;
	private static int $changed = 0;

	public static function clear(): void
	{
		self::$time = 0;
		self::$changed = 0;
	}

	/**
	 * @param float $time
	 * @param int   $changed
	 */
	public static function from(float $time, int $changed): void
	{
		self::$time += $time;
		self::$changed += $changed;
	}

	/**
	 * @return float
	 */
	public static function getTime(): float
	{
		return self::$time;
	}

	/**
	 * @return int
	 */
	public static function getChanged(): int
	{
		return self::$changed;
	}
}