<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use platz1de\EasyEdit\selection\constructor\CubicConstructor;
use pocketmine\math\Vector3;
use pocketmine\world\format\Chunk;
use pocketmine\world\World;
use UnexpectedValueException;

class StaticBlockListSelection extends BlockListSelection
{
	public function useOnBlocks(Vector3 $place, Closure $closure, SelectionContext $context): void
	{
		CubicConstructor::betweenPoints($this->getPos1(), $this->getPos2(), $closure);
	}

	/**
	 * @param string $world
	 */
	public function setWorld(string $world): void
	{
		$this->world = $world;
	}

	/**
	 * splits into 3x3 Chunk pieces
	 * @param Vector3 $offset
	 * @return StaticBlockListSelection[]
	 */
	public function split(Vector3 $offset): array
	{
		if ($this->piece) {
			throw new UnexpectedValueException("Pieces are not split able");
		}

		//TODO: offset
		//TODO: split tiles
		$pieces = [];
		for ($x = $this->pos1->getX() >> 4; $x <= $this->pos2->getX() >> 4; $x += 3) {
			for ($z = $this->pos1->getZ() >> 4; $z <= $this->pos2->getZ() >> 4; $z += 3) {
				$piece = new StaticBlockListSelection($this->getPlayer(), $this->getWorldName(), new Vector3(max($x << 4, $this->pos1->getX()), max($this->pos1->getY(), World::Y_MIN), max($z << 4, $this->pos1->getZ())), new Vector3(min(($x << 4) + 47, $this->pos2->getX()), min($this->pos2->getY(), World::Y_MAX - 1), min(($z << 4) + 47, $this->pos2->getZ())), true);
				for ($chunkX = 0; $chunkX < 3; $chunkX++) {
					for ($chunkZ = 0; $chunkZ < 3; $chunkZ++) {
						$piece->getManager()->setChunk($x + $chunkX, $z + $chunkZ, $this->getManager()->getChunk($x + $chunkX, $z + $chunkZ) ?? new Chunk());
						$this->getManager()->setChunk($x + $chunkX, $z + $chunkZ, new Chunk());
					}
				}
				$pieces[] = $piece;
			}
		}
		return $pieces;
	}
}