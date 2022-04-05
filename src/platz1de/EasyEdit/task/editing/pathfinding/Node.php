<?php

namespace platz1de\EasyEdit\task\editing\pathfinding;

use Generator;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class Node
{
	public int $x;
	public int $y;
	public int $z;
	public int $hash;
	public float $g;
	public int $parentHash;
	public float $h;

	public function __construct(int $x, int $y, int $z, ?Node $parent, int $sx, int $sy, int $sz)
	{
		$this->x = $x;
		$this->y = $y;
		$this->z = $z;
		$this->hash = World::blockHash($x, $y, $z);
		if ($parent !== null) {
			$this->g = $parent->g + sqrt(abs($x - $parent->x) + abs($y - $parent->y) + abs($z - $parent->z));
			$this->parentHash = $parent->hash;
		} else {
			$this->g = 0;
			$this->parentHash = -1;
		}
		$this->h = abs($this->x - $sx) + abs($this->y - $sy) + abs($this->z - $sz);
	}

	public function getF(): float
	{
		return $this->g + 10 * $this->h;
	}

	public function checkG(Node $parent): void
	{
		$g = $parent->g + sqrt(abs($this->x - $parent->x) + abs($this->y - $parent->y) + abs($this->z - $parent->z));
		if ($g < $this->g) {
			$this->g = $g;
			$this->parentHash = $parent->hash;
		}
	}

	/**
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @return bool
	 */
	public function equals(int $x, int $y, int $z): bool
	{
		return $this->x === $x && $this->y === $y && $this->z === $z;
	}

	/**
	 * @param bool $allowDiagonal
	 * @return Generator|Vector3[]
	 * @phpstan-return Generator<int, Vector3, void, void>
	 * @noinspection PhpDocSignatureInspection
	 */
	public function getSides(bool $allowDiagonal): Generator
	{
		if ($allowDiagonal) {
			yield from $this->getSidesDiagonal();
		} else {
			yield from (new Vector3($this->x, $this->y, $this->z))->sides();
		}
	}

	/**
	 * @return Generator<Vector3>
	 * @phpstan-return Generator<int, Vector3, void, void>
	 */
	public function getSidesDiagonal(): Generator
	{
		for ($xi = -1; $xi <= 1; $xi++) {
			for ($yi = -1; $yi <= 1; $yi++) {
				for ($zi = -1; $zi <= 1; $zi++) {
					if ($xi !== 0 || $yi !== 0 || $zi !== 0) {
						yield new Vector3($this->x + $xi, $this->y + $yi, $this->z + $zi);
					}
				}
			}
		}
	}
}