<?php

namespace platz1de\EasyEdit\selection\constructor;

use BadMethodCallException;
use Closure;
use platz1de\EasyEdit\math\BlockOffsetVector;
use pocketmine\utils\Utils;

class RawShapeConstructor extends ShapeConstructor
{
	/**
	 * @param Closure $closure
	 * @param bool    $greedy Whether to request chunks (otherwise this will follow the parent selection shape)
	 */
	public function __construct(Closure $closure, private bool $greedy)
	{
		Utils::validateCallableSignature(static function (int $chunk): void { }, $closure);
		$this->closure = $closure;
	}

	public function getBlockCount(): int
	{
		return 0; //unknown
	}

	public function moveTo(int $chunk): void
	{
		$closure = $this->closure;
		$closure($chunk);
	}

	public function needsChunk(int $chunk): bool
	{
		return $this->greedy;
	}

	public function offset(BlockOffsetVector $offset): self
	{
		throw new BadMethodCallException("Raw shapes can't be offset");
	}
}