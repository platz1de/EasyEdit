<?php

namespace platz1de\EasyEdit\selection\constructor;

use Closure;
use platz1de\EasyEdit\math\BlockOffsetVector;
use platz1de\EasyEdit\math\BlockVector;

/**
 * The probably most pointless class in the world
 */
class SingleBlockConstructor extends ShapeConstructor
{
	/**
	 * @param Closure     $closure
	 * @param BlockVector $position
	 */
	public function __construct(Closure $closure, private BlockVector $position)
	{
		parent::__construct($closure);
	}

	public function getBlockCount(): int
	{
		return 1;
	}

	public function moveTo(int $chunk): void
	{
		if ($this->position->isInChunk($chunk)) {
			$closure = $this->closure;
			$closure($this->position->x, $this->position->y, $this->position->z);
		}
	}

	public function offset(BlockOffsetVector $offset): self
	{
		return new self($this->closure, $this->position->offset($offset));
	}
}