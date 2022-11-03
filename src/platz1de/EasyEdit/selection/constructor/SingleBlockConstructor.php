<?php

namespace platz1de\EasyEdit\selection\constructor;

use Closure;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\math\Vector3;

/**
 * The probably most pointless class in the world
 */
class SingleBlockConstructor extends ShapeConstructor
{
	private Vector3 $position;

	public function __construct(Closure $closure, Vector3 $position)
	{
		parent::__construct($closure);
		$this->position = $position->floor();
	}

	public function getBlockCount(): int
	{
		return 1;
	}

	public function moveTo(int $chunk): void
	{
		if ((VectorUtils::isVectorInChunk($this->position, $chunk))) {
			$closure = $this->closure;
			$closure($this->position->getX(), $this->position->getY(), $this->position->getZ());
		}
	}

	public function offset(Vector3 $offset): self
	{
		return new self($this->closure, $this->position->addVector($offset));
	}
}