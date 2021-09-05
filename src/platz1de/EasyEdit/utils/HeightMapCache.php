<?php

namespace platz1de\EasyEdit\utils;

use platz1de\EasyEdit\selection\Selection;
use pocketmine\block\Block;
use pocketmine\block\BlockLegacyIds;
use pocketmine\world\World;

class HeightMapCache
{
	//TODO: don't just delete this Blocks
	/**
	 * @var int[] these mess up the height calculation in different ways (this will never be complete, only the most important ones)
	 */
	private static array $ignore = [BlockLegacyIds::AIR,
		BlockLegacyIds::LOG, BlockLegacyIds::LOG2, BlockLegacyIds::LEAVES, BlockLegacyIds::LEAVES2, //trees
		BlockLegacyIds::YELLOW_FLOWER, BlockLegacyIds::RED_FLOWER, BlockLegacyIds::TALLGRASS, //flowers and stuff
		BlockLegacyIds::FLOWING_WATER, BlockLegacyIds::STILL_WATER, BlockLegacyIds::FLOWING_LAVA, BlockLegacyIds::STILL_LAVA, //fluids
		BlockLegacyIds::SNOW_LAYER
	];

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
}