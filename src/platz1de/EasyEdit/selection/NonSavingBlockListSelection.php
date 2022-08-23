<?php

namespace platz1de\EasyEdit\selection;

use BadMethodCallException;
use Closure;
use pocketmine\math\Vector3;

/**
 * Used for tasks that don't produce changes by default
 */
class NonSavingBlockListSelection extends BlockListSelection
{
	public function __construct()
	{
		parent::__construct("", Vector3::zero(), Vector3::zero());
	}

	public function addBlock(int $x, int $y, int $z, int $id, bool $overwrite = true): void
	{
		throw new BadMethodCallException("Task was expected to not affect any blocks");
	}

	public function getBlockCount(): int
	{
		return 0;
	}

	public function createSafeClone(): BlockListSelection
	{
		throw new BadMethodCallException("Cannot clone a non-saving selection");
	}

	public function getNeededChunks(): array
	{
		return [];
	}

	public function shouldBeCached(int $x, int $z): bool
	{
		return false;
	}

	public function useOnBlocks(Closure $closure, SelectionContext $context, Selection $full, Vector3 $min, Vector3 $max): void
	{
		throw new BadMethodCallException("Cannot clone a non-saving selection for setting");
	}
}