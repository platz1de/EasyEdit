<?php

namespace platz1de\EasyEdit\world;

use platz1de\EasyEdit\selection\Selection;
use pocketmine\block\Block;
use pocketmine\world\World;

class HeightMapCache
{
	//TODO: don't just delete this Blocks
	/**
	 * @var int[] these mess up the height calculation in different ways
	 */
	private static array $ignore;

	private static bool $loaded;
	/**
	 * @var int[][][] starting height -> thickness (downwards)
	 */
	private static array $heightMap = [];
	private static array $reverseHeightMap = [];

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
					$y = $max->getFloorY();
					if (!in_array($iterator->getBlockAt($x, $y, $z) >> Block::INTERNAL_METADATA_BITS, self::$ignore, true)) {
						$y = World::Y_MAX - 1;
					}
					while ($y >= $min->getFloorY()) {
						while ($y >= $min->getFloorY() && in_array($iterator->getBlockAt($x, $y, $z) >> Block::INTERNAL_METADATA_BITS, self::$ignore, true)) {
							$y--;
						}
						$c = $y;
						while ($y >= $min->getFloorY() && !in_array($iterator->getBlockAt($x, $y, $z) >> Block::INTERNAL_METADATA_BITS, self::$ignore, true)) {
							$y--;
						}
						self::$heightMap[$x][$z][$c] = $c - $y + 1;
						self::$reverseHeightMap[$x][$z][$y] = $c - $y + 1;
					}
				}
			}
			self::$loaded = true;
		}
	}

	/**
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @return int blocks upwards until next air-like block
	 */
	public static function searchUpwards(int $x, int $y, int $z): int
	{
		$local = self::$heightMap[$x][$z];
		$search = $y;
		while ($search < World::Y_MAX && !isset($local[$search])) {
			$search++;
		}
		$depth = $search - $y + 1;
		return $depth > 0 ? $depth : 0;
	}

	/**
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @return int blocks downwards until next air-like block
	 */
	public static function searchDownwards(int $x, int $y, int $z): int
	{
		$local = self::$reverseHeightMap[$x][$z];
		$search = $y;
		while ($search < World::Y_MAX && !isset($local[$search])) {
			$search++;
		}
		$depth = $y - $search + 1;
		return $depth > 0 ? $depth : 0;
	}

	public static function prepare(): void
	{
		self::$loaded = false;
		self::$heightMap = [];
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