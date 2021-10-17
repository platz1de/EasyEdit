<?php

namespace platz1de\EasyEdit\cache;

use BadMethodCallException;
use Closure;
use platz1de\EasyEdit\thread\output\TaskResultData;
use pocketmine\utils\Utils;

class ClosureCache
{
	/**
	 * @var Closure[]
	 */
	private static array $closures = [];

	/**
	 * @param Closure $closure
	 * @return void
	 */
	public static function addToCache(Closure $closure): void
	{
		self::$closures[] = $closure;
		Utils::validateCallableSignature(static function (TaskResultData $result): void { }, $closure);
	}

	/**
	 * @param TaskResultData $result
	 */
	public static function execute(TaskResultData $result): void
	{
		$closure = array_shift(self::$closures);
		if ($closure === null) {
			throw new BadMethodCallException("Tried executing closure with none cached");
		}
		$closure($result);
	}
}