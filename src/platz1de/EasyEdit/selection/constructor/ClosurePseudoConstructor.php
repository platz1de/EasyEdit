<?php

namespace platz1de\EasyEdit\selection\constructor;

use Closure;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\math\Vector3;

/**
 * The probably most pointless class in the world
 */
class ClosurePseudoConstructor extends ShapeConstructor
{
	private ShapeConstructor $child;

	public function __construct(Closure $closure, )
	{
		parent::__construct($closure);
		$this->position = $position->floor();
	}

	public function getBlockCount(): int
	{
		return $this->child->getBlockCount();
	}

	public function moveTo(int $chunk): void
	{
		($this->closure)($chunk);
	}

	public function offset(Vector3 $offset): self
	{
		return new self($this->closure, $this->child->offset($offset));
	}
}