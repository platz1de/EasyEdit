<?php

namespace platz1de\EasyEdit\pattern\logic\relation;

use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\pattern\type\BlockPatternData;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\world\ChunkController;
use platz1de\EasyEdit\world\HeightMapCache;
use pocketmine\block\Block;
use pocketmine\math\Axis;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class EmbedPattern extends Pattern
{
	use BlockPatternData;

	/**
	 * @param int             $x
	 * @param int             $y
	 * @param int             $z
	 * @param ChunkController $iterator
	 * @param Selection       $current
	 * @param Selection       $total
	 * @return bool
	 */
	public function isValidAt(int $x, int $y, int $z, ChunkController $iterator, Selection $current, Selection $total): bool
	{
		if (in_array($iterator->getBlock($x, $y, $z) >> Block::INTERNAL_METADATA_BITS, HeightMapCache::getIgnore(), true)) {
			return false;
		}
		if ($y + 1 < World::Y_MAX && !in_array($iterator->getBlock($x, $y + 1, $z) >> Block::INTERNAL_METADATA_BITS, HeightMapCache::getIgnore(), true)) {
			return false;
		}
		foreach ((new Vector3($x, $y, $z))->sidesAroundAxis(Axis::Y) as $vector) {
			if ($y + 1 < World::Y_MAX && $this->block->equals($iterator->getBlock($vector->getFloorX(), $y + 1, $vector->getFloorZ()))) {
				return true;
			}
		}
		return false;
	}
}