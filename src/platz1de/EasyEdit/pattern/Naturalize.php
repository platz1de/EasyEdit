<?php

namespace platz1de\EasyEdit\pattern;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIds;
use pocketmine\level\Level;
use pocketmine\level\utils\SubChunkIteratorManager;

class Naturalize extends Pattern
{
	/**
	 * @param int                     $x
	 * @param int                     $y
	 * @param int                     $z
	 * @param SubChunkIteratorManager $iterator
	 * @return bool
	 */
	public function isValidAt(int $x, int $y, int $z, SubChunkIteratorManager $iterator): bool
	{
		$iterator->moveTo($x, $y, $z);
		return $iterator->currentSubChunk->getBlockId($x & 0x0f, $y & 0x0f, $z & 0x0f) !== 0;
	}

	/**
	 * @param int                     $x
	 * @param int                     $y
	 * @param int                     $z
	 * @param SubChunkIteratorManager $iterator
	 * @return Block|null
	 */
	public function getFor(int $x, int $y, int $z, SubChunkIteratorManager $iterator): ?Block
	{
		$i = 1;
		while ($y + $i <= Level::Y_MAX && $iterator->currentSubChunk->getBlockId($x & 0x0f, $y + $i & 0x0f, $z & 0x0f) !== 0){
			$i++;
		}
		switch ($i){
			case 1:
				return $this->pieces[0]->getFor($x, $y, $z, $iterator);
			case 2:
			case 3:
				return $this->pieces[1]->getFor($x, $y, $z, $iterator);
			default:
				return $this->pieces[2]->getFor($x, $y, $z, $iterator);
		}
	}

	public function check(): void
	{
		if (!isset($this->pieces[0])) {
			$this->pieces[0] = new BlockPattern(BlockFactory::get(BlockIds::GRASS));
		}
		if (!isset($this->pieces[1])) {
			$this->pieces[1] = new BlockPattern(BlockFactory::get(BlockIds::DIRT));
		}
		if (!isset($this->pieces[2])) {
			$this->pieces[2] = new BlockPattern(BlockFactory::get(BlockIds::STONE));
		}
	}
}