<?php

namespace platz1de\EasyEdit\pattern\block;

use platz1de\EasyEdit\pattern\type\EmptyPatternData;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\world\ChunkController;
use platz1de\EasyEdit\world\HeightMapCache;
use pocketmine\block\Block;
use pocketmine\utils\AssumptionFailedError;

class SolidBlock extends BlockType
{
	use EmptyPatternData;

	/**
	 * @param int             $x
	 * @param int             $y
	 * @param int             $z
	 * @param ChunkController $iterator
	 * @param Selection       $current
	 * @param Selection       $total
	 * @return int
	 */
	public function getFor(int $x, int &$y, int $z, ChunkController $iterator, Selection $current, Selection $total): int
	{
		throw new AssumptionFailedError("Solid block group should only be used in comparison context");
	}

	/**
	 * @param int $fullBlock
	 * @return bool
	 */
	public function equals(int $fullBlock): bool
	{
		return !in_array($fullBlock >> Block::INTERNAL_METADATA_BITS, HeightMapCache::getIgnore(), true);
	}

	/**
	 * @param SelectionContext $context
	 */
	public function applySelectionContext(SelectionContext $context): void
	{
		throw new AssumptionFailedError("Solid block group should only be used in comparison context");
	}
}