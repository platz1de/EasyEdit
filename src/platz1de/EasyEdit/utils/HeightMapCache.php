<?php

namespace platz1de\EasyEdit\utils;

use platz1de\EasyEdit\selection\Selection;
use pocketmine\level\utils\SubChunkIteratorManager;

class HeightMapCache
{
	/**
	 * @var
	 */
	private static $loaded;
	/**
	 * @var int[][]
	 */
	private static $highest = [];
	/**
	 * @var int[][]
	 */
	private static $lowest = [];

	/**
	 * @param SubChunkIteratorManager $iterator
	 * @param Selection               $selection
	 */
	public static function load(SubChunkIteratorManager $iterator, Selection $selection): void
	{
		if (!self::$loaded) {
			$min = $selection->getCubicStart()->subtract(1, 1, 1);
			$max = $min->add($selection->getRealSize())->add(1, 1, 1);
			for ($x = $min->getX(); $x <= $max->getX(); $x++) {
				for ($z = $min->getZ(); $z <= $max->getZ(); $z++) {
					$iterator->moveTo($x, 0, $z);
					$y = $min->getY();
					while ($y <= $max->getY() && $iterator->currentChunk->getBlockId($x & 0x0f, $y, $z & 0x0f) === 0) {
						$y++;
					}
					if ($y < $max->getY()) {
						self::$lowest[$x][$z] = $y;
					} else {
						self::$lowest[$x][$z] = null;
					}
					while ($y <= $max->getY() && $iterator->currentChunk->getBlockId($x & 0x0f, $y, $z & 0x0f) !== 0) {
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
}