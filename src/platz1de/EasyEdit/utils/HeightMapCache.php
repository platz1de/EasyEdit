<?php

namespace platz1de\EasyEdit\utils;

use platz1de\EasyEdit\selection\Selection;
use pocketmine\block\Block;

class HeightMapCache
{
	//TODO: don't just delete this Blocks
	/**
	 * @var int[] these mess up the height calculation in different ways
	 */
	private static array $ignore;

	private static bool $loaded;
	/**
	 * @var int[][]
	 */
	private static array $highest = [];
	/**
	 * @var int[][]
	 */
	private static array $lowest = [];

	/**
	 * @param SafeSubChunkExplorer $iterator
	 * @param Selection            $selection
	 */
	public static function load(SafeSubChunkExplorer $iterator, Selection $selection): void
	{
		if (!self::$loaded) {
			$min = $selection->getCubicStart()->subtract(1, 1, 1);
			$max = $selection->getCubicEnd()->add(1, 1, 1);
			for ($x = $min->getFloorX(); $x <= $max->getX(); $x++) {
				for ($z = $min->getFloorZ(); $z <= $max->getZ(); $z++) {
					$y = $min->getFloorY();
					while ($y <= $max->getFloorY() && in_array($iterator->getBlockAt($x, $y, $z) >> Block::INTERNAL_METADATA_BITS, self::$ignore, true)) {
						$y++;
					}
					if ($y < $max->getY()) {
						self::$lowest[$x][$z] = $y;
					} else {
						self::$lowest[$x][$z] = null;
					}

					while ($y <= $max->getFloorY() && !in_array($iterator->getBlockAt($x, $y, $z) >> Block::INTERNAL_METADATA_BITS, self::$ignore, true)) {
						$y++;
					}
					if ($y < $max->getY()) {
						self::$highest[$x][$z] = $y - 1;
					} else {
						self::$highest[$x][$z] = null;
					}
				}
			}
			self::$loaded = true;
		}
	}

	/**
	 * @param int $x
	 * @param int $z
	 * @return int|null
	 */
	public static function getHighest(int $x, int $z): ?int
	{
		return self::$highest[$x][$z];
	}

	/**
	 * @param int $x
	 * @param int $z
	 * @return int|null
	 */
	public static function getLowest(int $x, int $z): ?int
	{
		return self::$lowest[$x][$z];
	}

	public static function prepare(): void
	{
		self::$loaded = false;
	}

	/**
	 * @param int[] $ignore
	 */
	public static function setIgnore(array $ignore): void
	{
		self::$ignore = $ignore;
	}

	/**
	 * @return int[]
	 */
	public static function getIgnore(): array
	{
		return self::$ignore;
	}
}