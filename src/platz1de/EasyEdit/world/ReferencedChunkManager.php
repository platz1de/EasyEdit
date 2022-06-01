<?php

namespace platz1de\EasyEdit\world;

use Closure;
use pocketmine\math\Vector3;
use pocketmine\world\World;
use UnexpectedValueException;

class ReferencedChunkManager
{
	use ReferencedWorldHolder;

	/**
	 * @var ChunkInformation[]
	 */
	private array $chunks = [];

	public function __construct(string $world)
	{
		$this->world = $world;
	}

	public function getChunk(int $hash): ChunkInformation
	{
		return $this->chunks[$hash] ?? throw new UnexpectedValueException("Chunk " . $hash . " is not loaded");
	}

	public function setChunk(int $hash, ?ChunkInformation $chunk): void
	{
		if ($chunk === null) {
			unset($this->chunks[$hash]);
		} else {
			$this->chunks[$hash] = $chunk;
		}
	}

	public function cleanChunks(): void
	{
		$this->chunks = [];
	}

	/**
	 * @return ChunkInformation[]
	 */
	public function getChunks(): array
	{
		return $this->chunks;
	}

	/**
	 * @param Closure $closure
	 */
	public function filterChunks(Closure $closure): void
	{
		$this->chunks = $closure($this->chunks);
	}

	/**
	 * @param Vector3 $pos1
	 * @param Vector3 $pos2
	 */
	public function loadBetween(Vector3 $pos1, Vector3 $pos2): void
	{
		for ($x = $pos1->getX() >> 4; $x <= $pos2->getX() >> 4; $x++) {
			for ($z = $pos1->getZ() >> 4; $z <= $pos2->getZ() >> 4; $z++) {
				$this->setChunk(World::chunkHash($x, $z), ChunkInformation::empty());
			}
		}
	}

	/**
	 * @param int $hash
	 */
	public function loadIfNeeded(int $hash): void
	{
		if (!isset($this->chunks[$hash])) {
			$this->setChunk($hash, ChunkInformation::empty());
		}
	}

	public function __clone(): void
	{
		$this->chunks = array_map(static function (ChunkInformation $chunk): ChunkInformation {
			return clone $chunk;
		}, $this->chunks);
	}
}