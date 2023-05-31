<?php

namespace platz1de\EasyEdit\selection\constructor;

use Closure;
use platz1de\EasyEdit\math\BlockOffsetVector;
use platz1de\EasyEdit\math\BlockVector;
use pocketmine\math\Vector3;
use pocketmine\math\VoxelRayTrace;
use pocketmine\world\World;
use RuntimeException;

class LinearConstructor extends ShapeConstructor
{
	private int $a;
	private int $b;
	private int $c;

	public function __construct(private BlockVector $start, private BlockVector $end, Closure $closure)
	{
		parent::__construct($closure);
		$this->a = $this->end->x - $this->start->x;
		$this->b = $this->end->y - $this->start->y;
		$this->c = $this->end->z - $this->start->z;
	}

	public function getBlockCount(): int
	{
		return abs($this->start->x - $this->end->x) + abs($this->start->y - $this->end->y) + abs($this->start->z - $this->end->z) + 1;
	}

	public function moveTo(int $chunk): void
	{
		World::getXZ($chunk, $x, $z);
		//We don't know where the line enters or leaves the chunk, so we have to calculate it
		$results = [];
		if ($this->start->x >> 4 === $x && $this->start->z >> 4 === $z) {
			$results[] = $this->start->toVector()->add(0.5, 0.5, 0.5);
		}
		if ($this->end->x >> 4 === $x && $this->end->z >> 4 === $z) {
			$results[] = $this->end->toVector()->add(0.5, 0.5, 0.5);
		}
		if (count($results) !== 2) {
			$x <<= 4;
			$z <<= 4;
			//our line is defined by: x = a * t + x1, y = b * t + y1, z = c * t + z1
			//we can resolve t for the chunk borders and check if their according position is in the chunk
			if ($this->a !== 0) {
				$this->checkT($results, $x, $z, ($x - $this->start->x - 0.5) / $this->a, true);
				$this->checkT($results, $x, $z, ($x + 15.5 - $this->start->x) / $this->a, true);
			}
			if ($this->c !== 0) {
				$this->checkT($results, $x, $z, ($z - $this->start->z - 0.5) / $this->c, false);
				$this->checkT($results, $x, $z, ($z + 15.5 - $this->start->z) / $this->c, false);
			}
		}
		if (count($results) !== 2) {
			throw new RuntimeException("Could not find start and end of line in chunk $chunk");
		}
		$closure = $this->closure;
		foreach (VoxelRayTrace::betweenPoints($results[0], $results[1]) as $pos) {
			if ($pos->x >= $x && $pos->x < $x + 16 && $pos->z >= $z && $pos->z < $z + 16) $closure($pos->x, $pos->y, $pos->z);
		}
	}

	/**
	 * @param Vector3[] $results
	 * @param int       $x
	 * @param int       $z
	 * @param float     $t
	 * @param bool      $isX
	 */
	private function checkT(array &$results, int $x, int $z, float $t, bool $isX): void
	{
		if ($t < 0 || $t > 1) {
			return;
		}
		if ($this->b * $t + $this->start->y + 0.5 < World::Y_MIN || $this->b * $t + $this->start->y + 0.5 >= World::Y_MAX) {
			return;
		}
		if ($isX) {
			if ($this->c * $t + $this->start->z + 0.5 < $z || $this->c * $t + $this->start->z + 0.5 >= $z + 16) {
				return;
			}
		} elseif ($this->a * $t + $this->start->x + 0.5 < $x || $this->a * $t + $this->start->x + 0.5 >= $x + 16) {
			return;
		}
		$results[] = new Vector3($this->a * $t + $this->start->x + 0.5, $this->b * $t + $this->start->y + 0.5, $this->c * $t + $this->start->z + 0.5);
	}

	public function needsChunk(int $chunk): bool
	{
		return true;
	}

	public function offset(BlockOffsetVector $offset): ShapeConstructor
	{
		return new self($this->start->offset($offset), $this->end->offset($offset), $this->closure);
	}
}