<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use UnexpectedValueException;

class StaticBlockListSelection extends BlockListSelection
{
	/**
	 * @param Position $place
	 * @return array
	 */
	public function getNeededChunks(Position $place): array
	{
		return parent::getNeededChunks(Position::fromObject(new Vector3(0, 0, 0), $this->getLevel()));
	}

	/**
	 * @param Vector3 $place
	 * @param Closure $closure
	 */
	public function useOnBlocks(Vector3 $place, Closure $closure): void
	{
		parent::useOnBlocks(new Vector3(), $closure);
	}

	/**
	 * splits into 3x3 Chunk pieces
	 * @return array
	 */
	public function split(): array
	{
		if ($this->piece) {
			throw new UnexpectedValueException("Pieces are not split able");
		}

		$level = $this->getLevel();
		if ($level instanceof Level) {
			$level = $level->getFolderName();
		}
		$pieces = [];
		for ($x = ($this->pos1->getX()) >> 4; $x <= ($this->pos2->getX()) >> 4; $x += 3) {
			for ($z = ($this->pos1->getZ()) >> 4; $z <= ($this->pos2->getZ()) >> 4; $z += 3) {
				$piece = new StaticBlockListSelection($this->getPlayer(), $level, new Vector3(max($x << 4, $this->pos1->getX()), max($this->pos1->getY(), 0), max($z << 4, $this->pos1->getZ())), new Vector3(min(($x << 4) + 47, $this->pos2->getX()), min($this->pos2->getY(), Level::Y_MASK), min(($z << 4) + 47, $this->pos2->getZ())), true);
				for ($chunkX = 0; $chunkX < 3; $chunkX++) {
					for ($chunkZ = 0; $chunkZ < 3; $chunkZ++) {
						$piece->getManager()->setChunk($x + $chunkX, $z + $chunkZ, $this->getManager()->getChunk($x + $chunkX, $z + $chunkZ));
					}
				}
				$pieces[] = $piece;
			}
		}
		return $pieces;
	}
}