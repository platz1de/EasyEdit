<?php

namespace platz1de\EasyEdit\pattern\functional;

use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\utils\SafeSubChunkIteratorManager;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIds;
use pocketmine\level\Level;

class NaturalizePattern extends Pattern
{
	/**
	 * @param int                         $x
	 * @param int                         $y
	 * @param int                         $z
	 * @param SafeSubChunkIteratorManager $iterator
	 * @param Selection                   $selection
	 * @return bool
	 */
	public function isValidAt(int $x, int $y, int $z, SafeSubChunkIteratorManager $iterator, Selection $selection): bool
	{
		$iterator->moveTo($x, $y, $z);
		return $iterator->getCurrent()->getBlockId($x & 0x0f, $y & 0x0f, $z & 0x0f) !== 0;
	}

	/**
	 * @param int                         $x
	 * @param int                         $y
	 * @param int                         $z
	 * @param SafeSubChunkIteratorManager $iterator
	 * @param Selection                   $selection
	 * @return Block|null
	 */
	public function getFor(int $x, int $y, int $z, SafeSubChunkIteratorManager $iterator, Selection $selection): ?Block
	{
		$i = 1;
		$iterator->moveTo($x, $y, $z);
		while ($y + $i < Level::Y_MAX && $iterator->getChunk()->getBlockId($x & 0x0f, $y + $i, $z & 0x0f) !== 0) {
			$i++;
		}
		switch ($i) {
			case 1:
				return $this->pieces[0]->getFor($x, $y, $z, $iterator, $selection);
			case 2:
			case 3:
				return $this->pieces[1]->getFor($x, $y, $z, $iterator, $selection);
			default:
				return $this->pieces[2]->getFor($x, $y, $z, $iterator, $selection);
		}
	}

	public function check(): void
	{
		if (!isset($this->pieces[0])) {
			$this->pieces[0] = new StaticBlock(BlockFactory::get(BlockIds::GRASS));
		}
		if (!isset($this->pieces[1])) {
			$this->pieces[1] = new StaticBlock(BlockFactory::get(BlockIds::DIRT));
		}
		if (!isset($this->pieces[2])) {
			$this->pieces[2] = new StaticBlock(BlockFactory::get(BlockIds::STONE));
		}
	}
}