<?php

namespace platz1de\EasyEdit\utils;

use platz1de\EasyEdit\selection\Selection;
use pocketmine\block\BlockIds;
use pocketmine\level\utils\SubChunkIteratorManager;

class HeightMapCache
{
	//TODO: don't just delete this Blocks
	/**
	 * @var int[] these mess up the height calculation in different ways (this will never be complete, only the most important ones)
	 */
	private static $ignore = [BlockIds::AIR,
		BlockIds::WOOD, BlockIds::WOOD2, BlockIds::LEAVES, BlockIds::LEAVES2, //trees
		BlockIds::YELLOW_FLOWER, BlockIds::RED_FLOWER, BlockIds::TALLGRASS, //flowers and stuff
		BlockIds::FLOWING_WATER, BlockIds::STILL_WATER, BlockIds::FLOWING_LAVA, BlockIds::STILL_LAVA, //fluids
		BlockIds::SNOW_LAYER
	];

	/**
	 * @var bool
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
			$max = $selection->getCubicStart()->add($selection->getRealSize());
			for ($x = $min->getX(); $x <= $max->getX(); $x++) {
				for ($z = $min->getZ(); $z <= $max->getZ(); $z++) {
					$iterator->moveTo($x, 0, $z);
					$y = $min->getY();
					while ($y <= $max->getY() && in_array($iterator->currentChunk->getBlockId($x & 0x0f, $y, $z & 0x0f), self::$ignore, true)) {
						$y++;
					}
					if ($y < $max->getY()) {
						self::$lowest[$x][$z] = $y;
					} else {
						self::$lowest[$x][$z] = null;
					}

					while ($y <= $max->getY() && !in_array($iterator->currentChunk->getBlockId($x & 0x0f, $y, $z & 0x0f), self::$ignore, true)) {
						$y++;
					}
					if ($y < $max->getY()) {
						self::$highest[$x][$z] = $y - 1;
					} else {
						self::$highest[$x][$z] = $max->getY();
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