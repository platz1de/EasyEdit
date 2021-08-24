<?php

namespace platz1de\EasyEdit\pattern\functional;

use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\utils\SafeSubChunkExplorer;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\world\World;

class NaturalizePattern extends Pattern
{
	/**
	 * @param int                  $x
	 * @param int                  $y
	 * @param int                  $z
	 * @param SafeSubChunkExplorer $iterator
	 * @param Selection            $selection
	 * @return bool
	 */
	public function isValidAt(int $x, int $y, int $z, SafeSubChunkExplorer $iterator, Selection $selection): bool
	{
		$iterator->moveTo($x, $y, $z);
		return $iterator->getCurrent()->getFullBlock($x & 0x0f, $y & 0x0f, $z & 0x0f) !== 0;
	}

	/**
	 * @param int                  $x
	 * @param int                  $y
	 * @param int                  $z
	 * @param SafeSubChunkExplorer $iterator
	 * @param Selection            $selection
	 * @return Block|null
	 */
	public function getFor(int $x, int $y, int $z, SafeSubChunkExplorer $iterator, Selection $selection): ?Block
	{
		$i = 1;
		$iterator->moveTo($x, $y, $z);
		while ($y + $i < World::Y_MAX && $iterator->getChunk()->getFullBlock($x & 0x0f, $y + $i, $z & 0x0f) !== 0) {
			$i++;
		}
		return match ($i) {
			1 => $this->pieces[0]->getFor($x, $y, $z, $iterator, $selection),
			2, 3 => $this->pieces[1]->getFor($x, $y, $z, $iterator, $selection),
			default => $this->pieces[2]->getFor($x, $y, $z, $iterator, $selection),
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
}