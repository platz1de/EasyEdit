<?php

namespace platz1de\EasyEdit\selection;

use platz1de\EasyEdit\math\BlockVector;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\world\World;

class ExpandingStaticBlockListSelection extends StaticBlockListSelection
{
	/**
	 * @param string      $world
	 * @param BlockVector $pos
	 */
	public function __construct(string $world, BlockVector $pos)
	{
		parent::__construct($world, $pos, $pos);
		$this->getManager()->loadIfNeeded($pos->getChunkHash());
	}

	public function addBlock(int $x, int $y, int $z, int $id, bool $overwrite = true): void
	{
		VectorUtils::adjustBoundaries($x, $y, $z, $this->pos1, $this->pos2);
		$this->getManager()->loadIfNeeded(World::chunkHash($x >> 4, $z >> 4));
		parent::addBlock($x, $y, $z, $id, $overwrite);
	}
}