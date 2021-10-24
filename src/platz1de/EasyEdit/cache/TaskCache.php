<?php

namespace platz1de\EasyEdit\cache;

use Closure;

class TaskCache
{
	/**
	 * @var Closure[]
	 */
	private static array $cache = [];
	private static int $cacheId = 0;

	/**
	 * @param Closure $closure
	 * @return int
	 */
	public static function cache(Closure $closure): int
	{
		self::$cache[self::$cacheId] = $closure;
		return self::$cacheId++;
	}

	/**
	 * @param int $id
	 * @return Closure
	 */
	public static function get(int $id): Closure
	{
		$closure = self::$cache[$id];
		unset(self::$cache[$id]);
		return $closure;
	}
}