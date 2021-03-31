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
		HeightMapCache::load($iterator, $selection);

		$max = 0;
		$tMax = 0;
		$min = 0;
		$tMin = 0;
		for ($kernelX = -1; $kernelX <= 1; $kernelX++) {
			for ($kernelZ = -1; $kernelZ <= 1; $kernelZ++) {
				$m = HeightMapCache::getHighest($x + $kernelX, $z + $kernelZ);
				if ($m !== null) {
					$max += $m;
					$tMax++;
				}

				$m = HeightMapCache::getLowest($x + $kernelX, $z + $kernelZ);
				if ($m !== null) {
					$min += $m;
					$tMin++;
				}
			}
		}
		if ($tMax !== 0) {
			$max /= $tMax;
		} elseif ($tMin !== 0) {
			$max = Level::Y_MAX; //we don't want Problems if a selection is filled with Blocks on top
		}
		if ($tMin !== 0) {
			$min /= $tMin;
		}
		$max = round($max);
		$min = round($min);
		$oMax = HeightMapCache::getHighest($x, $z);
		$oMin = HeightMapCache::getLowest($x, $z);
		$oMid = ($oMin + $oMax) / 2;
		$mid = ($min + $max) / 2;

		if($tMin >= 5) {
			if ($y >= $mid && $y <= $max) {
				$k = ($y - $mid) / ($max - $mid);
				$gy = $oMid + round($k * ($oMax - $oMid));
				$iterator->moveTo($x, $gy, $z);
				return BlockFactory::get($iterator->currentSubChunk->getBlockId($x & 0x0f, $gy & 0x0f, $z & 0x0f), $iterator->currentSubChunk->getBlockData($x & 0x0f, $gy & 0x0f, $z & 0x0f));
			}

			if ($y <= $mid && $y >= $min) {
				$k = ($y - $mid) / ($min - $mid);
				$gy = $oMid + round($k * ($oMin - $oMid));
				$iterator->moveTo($x, $gy, $z);
				return BlockFactory::get($iterator->currentSubChunk->getBlockId($x & 0x0f, $gy & 0x0f, $z & 0x0f), $iterator->currentSubChunk->getBlockData($x & 0x0f, $gy & 0x0f, $z & 0x0f));
			}
		}

		return BlockFactory::get(0);
	}
}