<?php

namespace platz1de\EasyEdit\pattern\functional;

use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\utils\SafeSubChunkExplorer;
use pocketmine\block\VanillaBlocks;
use pocketmine\world\World;

class NaturalizePattern extends Pattern
{
	/**
	 * @param int                  $x
	 * @param int                  $y
	 * @param int                  $z
	 * @param SafeSubChunkExplorer $iterator
	 * @param Selection            $current
	 * @param Selection            $total
	 * @return bool
	 */
	public function isValidAt(int $x, int $y, int $z, SafeSubChunkExplorer $iterator, Selection $current, Selection $total): bool
	{
		return $iterator->getBlockAt($x, $y, $z) !== 0;
	}

	/**
	 * @param int                  $x
	 * @param int                  $y
	 * @param int                  $z
	 * @param SafeSubChunkExplorer $iterator
	 * @param Selection            $current
	 * @param Selection            $total
	 * @return int
	 */
	public function getFor(int $x, int $y, int $z, SafeSubChunkExplorer $iterator, Selection $current, Selection $total): int
	{
		$i = 1;
		while ($y + $i < World::Y_MAX && $iterator->getBlockAt($x, $y + $i, $z) !== 0) {
			$i++;
		}
		return match ($i) {
			1 => $this->pieces[0]->getFor($x, $y, $z, $iterator, $current, $total),
			2, 3 => $this->pieces[1]->getFor($x, $y, $z, $iterator, $current, $total),
			default => $this->pieces[2]->getFor($x, $y, $z, $iterator, $current, $total),
		};
	}

	public function check(): void
	{
		if (!isset($this->pieces[0])) {
			$this->pieces[0] = StaticBlock::from(VanillaBlocks::GRASS());
		}
		if (!isset($this->pieces[1])) {
			$this->pieces[1] = StaticBlock::from(VanillaBlocks::DIRT());
		}
		if (!isset($this->pieces[2])) {
			$this->pieces[2] = StaticBlock::from(VanillaBlocks::STONE());
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