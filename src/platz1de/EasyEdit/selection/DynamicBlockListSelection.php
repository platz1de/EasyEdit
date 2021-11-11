<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use platz1de\EasyEdit\selection\constructor\CubicConstructor;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\math\Vector3;
use pocketmine\world\Position;
use pocketmine\world\World;
use UnexpectedValueException;

class DynamicBlockListSelection extends BlockListSelection
{
	private Vector3 $point;

	/**
	 * DynamicBlockListSelection constructor.
	 * @param string       $player
	 * @param Vector3|null $place
	 * @param Vector3|null $pos1
	 * @param Vector3|null $pos2
	 * @param bool         $piece
	 */
	public function __construct(string $player, ?Vector3 $place = null, ?Vector3 $pos1 = null, ?Vector3 $pos2 = null, bool $piece = false)
	{
		if ($pos1 instanceof Vector3 && $pos2 instanceof Vector3) {
			$pos2 = $pos2->subtractVector($pos1);
		}
		parent::__construct($player, "", new Vector3(0, World::Y_MIN, 0), $pos2 ?? null, $piece);
		if ($pos1 instanceof Vector3 && $place instanceof Vector3) {
			$this->point = $place->subtractVector($pos1);
		}
	}

	/**
	 * @param Position $place
	 * @return int[]
	 */
	public function getNeededChunks(Vector3 $place): array
	{
		$start = $this->getCubicStart()->addVector($place)->subtractVector($this->getPoint());
		$end = $this->getCubicEnd()->addVector($place)->subtractVector($this->getPoint());

		$chunks = [];
		for ($x = $start->getX() >> 4; $x <= $end->getX() >> 4; $x++) {
			for ($z = $start->getZ() >> 4; $z <= $end->getZ() >> 4; $z++) {
				$chunks[] = World::chunkHash($x, $z);
			}
		}
		return $chunks;
	}

	/**
	 * @param int     $x
	 * @param int     $z
	 * @param Vector3 $place
	 * @return bool
	 */
	public function isChunkOfSelection(int $x, int $z, Vector3 $place): bool
	{
		$start = $this->getCubicStart()->addVector($place)->subtractVector($this->getPoint());
		$end = $this->getCubicEnd()->addVector($place)->subtractVector($this->getPoint());

		return $start->getX() >> 4 <= $x && $x <= $end->getX() >> 4 && $start->getZ() >> 4 <= $z && $z <= $end->getZ() >> 4;
	}

	/**
	 * @param Vector3          $place
	 * @param Closure          $closure
	 * @param SelectionContext $context
	 * @param Selection        $full
	 */
	public function useOnBlocks(Vector3 $place, Closure $closure, SelectionContext $context, Selection $full): void
	{
		CubicConstructor::betweenPoints($this->getPos1()->addVector($place), $this->getPos2()->addVector($place), $closure);
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

		//TODO: split tiles
		$pieces = [];
		$min = VectorUtils::enforceHeight($this->pos1->addVector($offset)->subtractVector($this->getPoint()));
		$max = VectorUtils::enforceHeight($this->pos2->addVector($offset)->subtractVector($this->getPoint()));
		for ($x = 0; $x <= ($max->getX() >> 4) - ($min->getX() >> 4); $x += 3) {
			for ($z = 0; $z <= ($max->getZ() >> 4) - ($min->getZ() >> 4); $z += 3) {
				$piece = new DynamicBlockListSelection($this->getPlayer(), null, null, null, true);
				$piece->setPoint($this->getPoint());
				$piece->setPos1($pos1 = new Vector3(max(($x << 4) - ($min->getX() & 0x0f), 0), World::Y_MIN, max(($z << 4) - ($min->getZ() & 0x0f), 0)));
				$piece->setPos2($pos2 = new Vector3(min(($x << 4) - ($min->getX() & 0x0f) + 47, $max->getX() - $min->getX()), World::Y_MIN + $max->getY() - $min->getY(), min(($z << 4) - ($min->getZ() & 0x0f) + 47, $max->getZ() - $min->getZ())));
				for ($chunkX = $pos1->getX() >> 4; $chunkX <= $pos2->getX() >> 4; $chunkX++) {
					for ($chunkZ = $pos1->getZ() >> 4; $chunkZ <= $pos2->getZ() >> 4; $chunkZ++) {
						$chunk = $this->getManager()->getChunk($chunkX, $chunkZ);
						if ($chunk !== null) {
							$piece->getManager()->setChunk($chunkX, $chunkZ, clone $chunk);
						}
					}
				}
				$pieces[] = $piece;
			}
		}
		$this->getManager()->cleanChunks();
		return $pieces;
	}
}