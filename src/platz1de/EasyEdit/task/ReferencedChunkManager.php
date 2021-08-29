<?php

namespace platz1de\EasyEdit\task;

use platz1de\EasyEdit\utils\ReferencedWorldHolder;
use pocketmine\world\format\Chunk;
use pocketmine\world\SimpleChunkManager;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class ReferencedChunkManager extends SimpleChunkManager
{
	use ReferencedWorldHolder;

	/**
	 * ReferencedChunkManager constructor.
	 * @param string $world
	 */
	public function __construct(string $world)
	{
		parent::__construct(World::Y_MIN, World::Y_MAX);
		$this->world = $world;
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