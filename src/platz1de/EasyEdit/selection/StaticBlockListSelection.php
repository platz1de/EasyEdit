<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use platz1de\EasyEdit\selection\constructor\CubicConstructor;
use pocketmine\math\Vector3;

class StaticBlockListSelection extends ChunkManagedBlockList
{
	/**
	 * @param Closure          $closure
	 * @param SelectionContext $context
	 * @param Vector3          $min
	 * @param Vector3          $max
	 */
	public function useOnBlocks(Closure $closure, SelectionContext $context, Vector3 $min, Vector3 $max): void
	{
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
		foreach ($this->getTiles() as $tile) {
			$clone->addTile($tile);
		}
		return $clone;
	}
}