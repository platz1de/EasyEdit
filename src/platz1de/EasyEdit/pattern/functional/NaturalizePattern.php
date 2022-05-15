<?php

namespace platz1de\EasyEdit\pattern\functional;

use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\world\HeightMapCache;
use platz1de\EasyEdit\world\ChunkController;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;

class NaturalizePattern extends Pattern
{
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
		return !in_array($iterator->getBlock($x, $y, $z) >> Block::INTERNAL_METADATA_BITS, HeightMapCache::getIgnore(), true);
	}

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
		HeightMapCache::load($iterator, $current);
		return match (HeightMapCache::searchAirUpwards($x, $y, $z)) {
			1 => $this->pieces[0]->getFor($x, $y, $z, $iterator, $current, $total),
			2, 3 => $this->pieces[1]->getFor($x, $y, $z, $iterator, $current, $total),
			default => $this->pieces[2]->getFor($x, $y, $z, $iterator, $current, $total),
		};
	}

	public function check(): void
	{
		if (!isset($this->pieces[0])) {
			$this->pieces[0] = StaticBlock::fromBlock(VanillaBlocks::GRASS());
		}
		if (!isset($this->pieces[1])) {
			$this->pieces[1] = StaticBlock::fromBlock(VanillaBlocks::DIRT());
		}
		if (!isset($this->pieces[2])) {
			$this->pieces[2] = StaticBlock::fromBlock(VanillaBlocks::STONE());
		}
	}

	/**
	 * @param SelectionContext $context
	 */
	public function applySelectionContext(SelectionContext $context): void
	{
		$context->includeWalls()->includeVerticals()->includeFilling();
	}
}