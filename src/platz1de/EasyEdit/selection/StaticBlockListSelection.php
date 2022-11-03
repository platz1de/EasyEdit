<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use Generator;
use platz1de\EasyEdit\selection\constructor\CubicConstructor;
use platz1de\EasyEdit\selection\constructor\ShapeConstructor;

class StaticBlockListSelection extends ChunkManagedBlockList
{
	/**
	 * @param Closure          $closure
	 * @param SelectionContext $context
	 * @return Generator<ShapeConstructor>
	 */
	public function asShapeConstructors(Closure $closure, SelectionContext $context): Generator
	{
		yield new CubicConstructor($closure, $this->getPos1(), $this->getPos2());
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