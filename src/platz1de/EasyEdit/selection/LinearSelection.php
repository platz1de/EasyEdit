<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use Generator;
use platz1de\EasyEdit\math\BlockVector;
use platz1de\EasyEdit\selection\constructor\LinearConstructor;
use platz1de\EasyEdit\selection\constructor\SingleBlockConstructor;
use pocketmine\math\VoxelRayTrace;
use pocketmine\world\World;

class LinearSelection extends Selection
{
	/**
	 * @param string      $world
	 * @param BlockVector $start
	 * @param BlockVector $end
	 */
	public function __construct(string $world, BlockVector $start, BlockVector $end)
	{
		parent::__construct($world, $start, $end);
	}

	protected function update(): void
	{
		//Stop messing with our positions
	}

	/**
	 * @return int[]
	 */
	public function getNeededChunks(): array
	{
		$chunks = [];
		foreach (VoxelRayTrace::betweenPoints($this->pos1->toVector()->add(0.5, 0.5, 0.5)->divide(16), $this->pos2->toVector()->add(0.5, 0.5, 0.5)->divide(16)) as $pos) {
			$chunks[] = World::chunkHash((int) $pos->x, (int) $pos->z);
		}
		return array_unique($chunks); //absolutely no idea how this can even happen, but it does
	}

	public function asShapeConstructors(Closure $closure, SelectionContext $context): Generator
	{
		if ($this->pos1->x === $this->pos2->x && $this->pos1->y === $this->pos2->y && $this->pos1->z === $this->pos2->z) {
			yield new SingleBlockConstructor($closure, $this->pos1);
		} else {
			yield new LinearConstructor($this->pos1, $this->pos2, $closure);
		}
	}
}