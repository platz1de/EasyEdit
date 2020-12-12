<?php

namespace platz1de\EasyEdit\pattern;


use pocketmine\block\Block;

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
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @return Block|null
	 */
	public function getFor(int $x, int $y, int $z): ?Block
	{
		return $this->pieces[0]->getFor($x, $y, $z);
	}

	public function isValidAt(int $x, int $y, int $z): bool
	{
		return !$this->pieces[0]->isValidAt($x, $y, $z);
	}
}