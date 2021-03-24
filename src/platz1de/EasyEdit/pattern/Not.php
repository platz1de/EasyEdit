<?php

namespace platz1de\EasyEdit\pattern;


use platz1de\EasyEdit\selection\Selection;
use pocketmine\block\Block;
use pocketmine\level\utils\SubChunkIteratorManager;

class Not extends Pattern
{
	/**
	 * Not constructor.
	 * @param Pattern $piece
	 */
	public function __construct(Pattern $piece)
	{
		parent::__construct([$piece], []);
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
		return $this->pieces[0]->getFor($x, $y, $z, $iterator, $selection);
	}

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
		return !$this->pieces[0]->isValidAt($x, $y, $z, $iterator, $selection);
	}
}