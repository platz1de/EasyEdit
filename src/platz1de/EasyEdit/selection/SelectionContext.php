<?php

namespace platz1de\EasyEdit\selection;

class SelectionContext
{
	public const NONE = 0;
	public const CENTER = 1;
	public const WALLS = 2;
	public const TOP_BOTTOM = 4;
	public const HOLLOW = 6;
	public const FILLING = 8; //includes center
	public const FULL = 14;

	/**
	 * @param int[] $contexts
	 * @return int
	 */
	public static function mergeContexts(array $contexts): int
	{
		if (in_array(self::FULL, $contexts)) {
			return self::FULL;
		}
		$c = self::NONE;
		foreach ($contexts as $context) {
			$c |= $context;
		}
		if (($c & self::CENTER) !== 0 && ($c & self::FILLING) !== 0) {
			return $c ^ self::CENTER; //Center is included in filling
		}
		return $c;
	}

	/**
	 * @param int $context
	 * @return string
	 */
	public static function getName(int $context): string
	{
		if ($context === self::NONE) {
			return "none";
		}
		if ($context === self::FULL) {
			return "full";
		}
		$c = [];
		if (($context & self::FILLING) !== 0) {
			$c[] = "filled";
		}
		if (($context & self::HOLLOW) === self::HOLLOW) {
			$c[] = "hollow";
		} else if (($context & self::TOP_BOTTOM) !== 0) {
			$c[] = "vertical";
		} else if (($context & self::WALLS) !== 0) {
			$c[] = "walled";
		}
		if (($context & self::CENTER) !== 0) {
			$c[] = "center";
		}
		return implode(" ", $c);
	}
}