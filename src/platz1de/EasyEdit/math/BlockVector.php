<?php

namespace platz1de\EasyEdit\math;

use pocketmine\world\World;

/**
 * Guaranteed to be part of the world grid
 */
class BlockVector extends BaseVector
{
	protected function validate(&$x, &$y, &$z): void
	{
		$y = min(max($y, World::Y_MIN), World::Y_MAX - 1);
	}

	public function diff(BlockVector|OffGridBlockVector $other): BlockOffsetVector
	{
		return new BlockOffsetVector($this->x - $other->x, $this->y - $other->y, $this->z - $other->z);
	}

	public function offset(BlockOffsetVector $offset): BlockVector
	{
		return new BlockVector($this->x + $offset->x, $this->y + $offset->y, $this->z + $offset->z);
	}

	public function isInChunk(int $chunk): bool
	{
		World::getXZ($chunk, $chunkX, $chunkZ);
		$chunkX <<= 4;
		$chunkZ <<= 4;
		return $this->x >= $chunkX && $this->x < $chunkX + 16 && $this->z >= $chunkZ && $this->z < $chunkZ + 16;
	}

	public function forceIntoChunkStart(int $chunk): BlockVector
	{
		World::getXZ($chunk, $chunkX, $chunkZ);
		$chunkX <<= 4;
		$chunkZ <<= 4;
		return new BlockVector(max($chunkX, $this->x), $this->y, max($chunkZ, $this->z));
	}

	public function forceIntoChunkEnd(int $chunk): BlockVector
	{
		World::getXZ($chunk, $chunkX, $chunkZ);
		$chunkX = ($chunkX << 4) + 15;
		$chunkZ = ($chunkZ << 4) + 15;
		return new BlockVector(min($chunkX, $this->x), $this->y, min($chunkZ, $this->z));
	}

	/**
	 * Not really a block, but we really don't need another class just for like 2 usages, it's always in-bounds anyway
	 * @return BlockVector
	 */
	public function toChunk(): BlockVector
	{
		return new BlockVector($this->x >> 4, $this->y >> 4, $this->z >> 4);
	}

	public function getChunkHash(): int
	{
		return World::chunkHash($this->x >> 4, $this->z >> 4);
	}
}