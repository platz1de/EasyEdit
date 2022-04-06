<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use platz1de\EasyEdit\selection\constructor\CubicConstructor;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\TileUtils;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\math\Vector3;
use pocketmine\world\World;
use UnexpectedValueException;

class DynamicBlockListSelection extends ChunkManagedBlockList
{
	private Vector3 $point;

	/**
	 * DynamicBlockListSelection constructor.
	 * @param string       $player
	 * @param Vector3      $end
	 * @param Vector3      $offset
	 * @param Vector3|null $startingOffset
	 * @param bool         $piece
	 */
	public function __construct(string $player, Vector3 $end, Vector3 $offset, ?Vector3 $startingOffset = null, bool $piece = false)
	{
		parent::__construct($player, "", $startingOffset ?? new Vector3(0, World::Y_MIN, 0), $end, $piece);
		$this->point = $offset;
	}

	/**
	 * @param string  $player
	 * @param Vector3 $place
	 * @param Vector3 $pos1
	 * @param Vector3 $pos2
	 * @return static
	 */
	public static function fromWorldPositions(string $player, Vector3 $place, Vector3 $pos1, Vector3 $pos2): self
	{
		return new self($player, $pos2->subtractVector($pos1)->up(World::Y_MIN), $pos1->subtractVector($place));
	}

	/**
	 * @return int[]
	 */
	public function getNeededChunks(): array
	{
		$start = $this->getCubicStart()->addVector($this->getPoint());
		$end = $this->getCubicEnd()->addVector($this->getPoint());

		$chunks = [];
		for ($x = $start->getX() >> 4; $x <= $end->getX() >> 4; $x++) {
			for ($z = $start->getZ() >> 4; $z <= $end->getZ() >> 4; $z++) {
				$chunks[] = World::chunkHash($x, $z);
			}
		}
		return $chunks;
	}

	/**
	 * @param int $x
	 * @param int $z
	 * @return bool
	 */
	public function isChunkOfSelection(int $x, int $z): bool
	{
		$start = $this->getCubicStart()->addVector($this->getPoint());
		$end = $this->getCubicEnd()->addVector($this->getPoint());

		return $start->getX() >> 4 <= $x && $x <= $end->getX() >> 4 && $start->getZ() >> 4 <= $z && $z <= $end->getZ() >> 4;
	}

	/**
	 * @param int $x
	 * @param int $z
	 * @return bool
	 */
	public function shouldBeCached(int $x, int $z): bool
	{
		$start = $this->getCubicStart()->addVector($this->getPoint());
		$end = $this->getCubicEnd()->addVector($this->getPoint());

		return $start->getX() >> 4 <= $x && $x <= $end->getX() >> 4 && ($z === $end->getZ() >> 4 || $z === ($end->getZ() >> 4) + 1);
	}

	/**
	 * @param Closure          $closure
	 * @param SelectionContext $context
	 * @param Selection        $full
	 */
	public function useOnBlocks(Closure $closure, SelectionContext $context, Selection $full): void
	{
		CubicConstructor::betweenPoints($this->getPos1()->addVector($this->getPoint()), $this->getPos2()->addVector($this->getPoint()), $closure);
	}

	public function init(Vector3 $place): void
	{
		parent::init($place);
		$this->pos1 = $this->pos1->addVector($place);
		$this->pos2 = $this->pos2->addVector($place);
	}

	/**
	 * @return Vector3
	 */
	public function getPoint(): Vector3
	{
		return $this->point;
	}

	/**
	 * @param Vector3 $point
	 */
	public function setPoint(Vector3 $point): void
	{
		$this->point = $point;
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);

		$stream->putVector($this->point);
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);

		$this->point = $stream->getVector();
	}

	/**
	 * splits into 3x3 Chunk pieces
	 * @param Vector3 $offset
	 * @return DynamicBlockListSelection[]
	 */
	public function split(Vector3 $offset): array
	{
		if ($this->piece) {
			throw new UnexpectedValueException("Pieces are not split able");
		}

		$pieces = [];
		$min = VectorUtils::enforceHeight($this->pos1->addVector($offset)->addVector($this->getPoint()));
		$max = VectorUtils::enforceHeight($this->pos2->addVector($offset)->addVector($this->getPoint()));
		for ($x = 0; $x <= ($max->getX() >> 4) - ($min->getX() >> 4); $x += 3) {
			for ($z = 0; $z <= ($max->getZ() >> 4) - ($min->getZ() >> 4); $z += 3) {
				$pos1 = new Vector3(max(($x << 4) - ($min->getX() & 0x0f), 0), World::Y_MIN, max(($z << 4) - ($min->getZ() & 0x0f), 0));
				$pos2 = new Vector3(min(($x << 4) - ($min->getX() & 0x0f) + 47, $max->getX() - $min->getX()), World::Y_MIN + $max->getY() - $min->getY(), min(($z << 4) - ($min->getZ() & 0x0f) + 47, $max->getZ() - $min->getZ()));
				$piece = new DynamicBlockListSelection($this->getPlayer(), $pos2, $this->getPoint(), $pos1, true);
				for ($chunkX = $pos1->getX() >> 4; $chunkX <= $pos2->getX() >> 4; $chunkX++) {
					for ($chunkZ = $pos1->getZ() >> 4; $chunkZ <= $pos2->getZ() >> 4; $chunkZ++) {
						$chunk = $this->getManager()->getChunk($chunkX, $chunkZ);
						if ($chunk !== null) {
							$piece->getManager()->setChunk($chunkX, $chunkZ, clone $chunk);
						}
					}
				}
				foreach ($this->getTiles() as $tile) {
					if (TileUtils::isBetweenVectors($tile, $piece->getPos1(), $piece->getPos2())) {
						$piece->addTile($tile);
					}
				}
				$pieces[] = $piece;
			}
		}
		$this->getManager()->cleanChunks();
		return $pieces;
	}

	public function createSafeClone(): DynamicBlockListSelection
	{
		$clone = new self($this->getPlayer(), $this->getPos2(), $this->getPoint(), $this->getPos1());
		foreach ($this->getManager()->getChunks() as $hash => $chunk) {
			World::getXZ($hash, $x, $z);
			$clone->getManager()->setChunk($x, $z, $chunk);
		}
		foreach ($this->getTiles() as $tile) {
			$this->addTile($tile);
		}
		return $clone;
	}
}