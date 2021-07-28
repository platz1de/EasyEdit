<?php

namespace platz1de\EasyEdit\task;

use platz1de\EasyEdit\utils\ReferencedLevelHolder;
use pocketmine\world\format\Chunk;
use pocketmine\world\SimpleChunkManager;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class ReferencedChunkManager extends SimpleChunkManager
{
	use ReferencedLevelHolder;

	/**
	 * ReferencedChunkManager constructor.
	 * @param string $level
	 */
	public function __construct(string $level)
	{
		parent::__construct(World::Y_MIN, World::Y_MAX);
		$this->world = $level;
	}

	/**
	 * @return Chunk[]
	 */
	public function getChunks(): array
	{
		return $this->chunks;
	}

	/**
	 * @param Vector3 $pos1
	 * @param Vector3 $pos2
	 */
	public function load(Vector3 $pos1, Vector3 $pos2): void
	{
		for ($x = $pos1->getX() >> 4; $x <= $pos2->getX() >> 4; $x++) {
			for ($z = $pos1->getZ() >> 4; $z <= $pos2->getZ() >> 4; $z++) {
				$this->setChunk($x, $z, new Chunk());
			}
		}
	}
}