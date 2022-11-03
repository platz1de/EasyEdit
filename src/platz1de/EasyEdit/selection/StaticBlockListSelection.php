<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use platz1de\EasyEdit\selection\constructor\CubicConstructor;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class StaticBlockListSelection extends ChunkManagedBlockList
{
	/**
	 * @param Closure          $closure
	 * @param SelectionContext $context
	 * @param int              $chunk
	 */
	public function useOnBlocks(Closure $closure, SelectionContext $context, int $chunk): void
	{
		$min = VectorUtils::getChunkPosition($chunk);
		$max = $min->add(15, World::Y_MAX - World::Y_MIN - 1, 15);
		CubicConstructor::betweenPoints(Vector3::maxComponents($this->getPos1(), $min), Vector3::minComponents($this->getPos2(), $max), $closure);
	}

	/**
	 * @param string $world
	 */
	public function setWorld(string $world): void
	{
		$this->world = $world;
	}

	public function createSafeClone(): StaticBlockListSelection
	{
		$clone = new self($this->getWorldName(), $this->getPos1(), $this->getPos2());
		foreach ($this->getManager()->getChunks() as $hash => $chunk) {
			$clone->getManager()->setChunk($hash, $chunk);
		}
		foreach ($this->getTiles($this->getPos1(), $this->getPos2()) as $tile) {
			$clone->addTile($tile);
		}
		return $clone;
	}
}