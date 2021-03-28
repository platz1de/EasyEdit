<?php

namespace platz1de\EasyEdit\pattern;

use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\utils\HeightMapCache;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\level\Level;
use pocketmine\level\utils\SubChunkIteratorManager;

class Smooth extends Pattern
{
	/**
	 * @param int                     $x
	 * @param int                     $y
	 * @param int                     $z
	 * @param SubChunkIteratorManager $iterator
	 * @param Selection               $selection
	 * @return bool
	 */
	public function isValidAt(int $x, int $y, int $z, SubChunkIteratorManager $iterator, Selection $selection): bool
	{
		return true;
	}

	/**
	 * @param int                     $x
	 * @param int                     $y
	 * @param int                     $z
	 * @param SubChunkIteratorManager $iterator
	 * @param Selection               $selection
	 * @return Block|null
	 */
	public function getFor(int $x, int $y, int $z, SubChunkIteratorManager $iterator, Selection $selection): ?Block
	{
		$height = 0;
		$min = 0;
		$tHeight = 0;
		$tMin = 0;
		for ($kernelX = -1; $kernelX <= 1; $kernelX++) {
			for ($kernelZ = -1; $kernelZ <= 1; $kernelZ++) {
				$m = HeightMapCache::getHighest($x + $kernelX, $z + $kernelZ);
				if($m !== null){
					$height += $m;
					$tHeight++;
				}

				$m = HeightMapCache::getLowest($x + $kernelX, $z + $kernelZ);
				if($m !== null){
					$min += $m;
					$tMin++;
				}
			}
		}
		if ($tHeight !== 0) {
			$height /= $tHeight;
		} elseif ($tMin !== 0) {
			$height = Level::Y_MAX; //we don't want Problems if a selection is filled with Blocks on top
		}
		if ($tMin !== 0) {
			$min /= $tMin;
		}
		$height = round($height);
		$min = round($min);
		//TODO: Actually use the right Blocks
		if ($y <= $height && $y >= $min && $tMin >= 5) {
			return BlockFactory::get(1);
		}
		return BlockFactory::get(0);
	}
}