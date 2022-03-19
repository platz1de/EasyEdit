<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use platz1de\EasyEdit\selection\constructor\CubicConstructor;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\math\Vector3;
use pocketmine\world\World;
use UnexpectedValueException;

class StackedCube extends Selection
{
	private Vector3 $direction;
	private Vector3 $min;
	private Vector3 $max;
	private bool $copyMode;

	/**
	 * StackedCube constructor.
	 * @param string       $player
	 * @param string       $world
	 * @param Vector3|null $pos1
	 * @param Vector3|null $pos2
	 * @param Vector3|null $direction
	 * @param Vector3|null $min
	 * @param Vector3|null $max
	 * @param bool         $piece
	 * @param bool         $copyMode
	 */
	public function __construct(string $player, string $world = "", ?Vector3 $pos1 = null, ?Vector3 $pos2 = null, ?Vector3 $direction = null, ?Vector3 $min = null, ?Vector3 $max = null, bool $piece = false, bool $copyMode = false)
	{
		parent::__construct($player, $world, $pos1, $pos2, $piece);
		$this->direction = $direction ?? Vector3::zero();
		if ($min !== null) {
			$this->min = $min;
		}
		if ($max !== null) {
			$this->max = $max;
		}
		$this->copyMode = $copyMode;
	}

	/**
	 * @return Vector3
	 */
	public function getDirection(): Vector3
	{
		return $this->direction;
	}

	/**
	 * @return int[]
	 */
	public function getNeededChunks(): array
	{
		$chunks = [];
		//TODO: Remove duplicates
		for ($x = $this->pos1->getX() >> 4; $x <= $this->pos2->getX() >> 4; $x++) {
			for ($z = $this->pos1->getZ() >> 4; $z <= $this->pos2->getZ() >> 4; $z++) {
				$chunks[] = World::chunkHash($x, $z);
			}
		}
		for ($x = $this->min->getX() >> 4; $x <= $this->max->getX() >> 4; $x++) {
			for ($z = $this->min->getZ() >> 4; $z <= $this->max->getZ() >> 4; $z++) {
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
		return $this->min->getX() >> 4 <= $x && $x <= $this->max->getX() >> 4 && $this->min->getZ() >> 4 <= $z && $z <= $this->max->getZ() >> 4;
	}

	/**
	 * @param int $x
	 * @param int $z
	 * @return bool
	 */
	public function shouldBeCached(int $x, int $z): bool
	{
		return $x >= $this->pos1->getX() >> 4 && $x <= $this->pos2->getX() >> 4 && $z >= $this->pos1->getZ() >> 4 && $z <= $this->pos2->getZ() >> 4;
	}

	/**
	 * @param Closure          $closure
	 * @param SelectionContext $context
	 * @param Selection        $full
	 */
	public function useOnBlocks(Closure $closure, SelectionContext $context, Selection $full): void
	{
		CubicConstructor::betweenPoints($this->min, $this->max, $closure);
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);

		$stream->putVector($this->direction);
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);

		$this->direction = $stream->getVector();
	}

	/**
	 * @return Vector3
	 */
	public function getCubicStart(): Vector3
	{
		return Vector3::minComponents($this->getPos1()->addVector(VectorUtils::multiply($this->getDirection()->normalize(), $this->getSize())), $this->getPos1()->addVector(VectorUtils::multiply($this->getDirection(), $this->getSize())));
	}

	/**
	 * @return Vector3
	 */
	public function getCubicEnd(): Vector3
	{
		return Vector3::maxComponents($this->getPos2()->addVector(VectorUtils::multiply($this->getDirection()->normalize(), $this->getSize())), $this->getPos2()->addVector(VectorUtils::multiply($this->getDirection(), $this->getSize())));
	}

	/**
	 * @return bool
	 */
	public function isCopyMode(): bool
	{
		return $this->copyMode;
	}

	/**
	 * @return Vector3
	 */
	public function getCopyOffset(): Vector3
	{
		return $this->pos1->subtractVector($this->min);
	}

	/**
	 * @param Vector3 $offset
	 * @return Selection[]
	 */
	public function split(Vector3 $offset): array
	{
		if ($this->piece) {
			throw new UnexpectedValueException("Pieces are not split able");
		}

		$pos1 = VectorUtils::enforceHeight($this->pos1->addVector($offset));
		$pos2 = VectorUtils::enforceHeight($this->pos2->addVector($offset));

		$min = Vector3::minComponents($this->getPos1()->addVector(VectorUtils::multiply($this->getDirection()->normalize(), $this->getSize())), $this->getPos1()->addVector(VectorUtils::multiply($this->getDirection(), $this->getSize())));
		$max = Vector3::maxComponents($this->getPos2()->addVector(VectorUtils::multiply($this->getDirection()->normalize(), $this->getSize())), $this->getPos2()->addVector(VectorUtils::multiply($this->getDirection(), $this->getSize())));

		$hasEmpty = ($pos2->subtractVector($pos1)->getX() > 32 && $this->direction->getX() !== 0) || ($pos2->subtractVector($pos1)->getZ() > 32 && $this->direction->getZ() !== 0);
		$pieces = [];
		//only 2x2 as we need 2 areas
		for ($x = $pos1->getX() >> 4; $x <= $pos2->getX() >> 4; $x += 2) {
			for ($z = $pos1->getZ() >> 4; $z <= $pos2->getZ() >> 4; $z += 2) {
				if ($hasEmpty) {
					$xSize = $pos2->getX() - $pos1->getX() + 1;
					$zSize = $pos2->getZ() - $pos1->getZ() + 1;
					for ($ox = 0; abs($ox) <= abs($this->direction->getX()); $this->direction->getX() > 0 ? $ox++ : $ox--) {
						for ($oz = 0; abs($oz) <= abs($this->direction->getZ()); $this->direction->getX() > 0 ? $oz++ : $oz--) {
							if ($ox === 0 && $oz === 0) {
								continue;
							}
							$pieces[] = new StackedCube($this->getPlayer(), $this->getWorldName(), new Vector3(max(($x << 4) - $offset->getX(), $pos1->getX()), $pos1->getY(), max(($z << 4) - $offset->getZ(), $pos1->getZ())), new Vector3(min((($x + 1) << 4) + 15 - $offset->getX(), $pos2->getX()), $pos2->getY(), min((($z + 1) << 4) + 15 - $offset->getZ(), $pos2->getZ())), $this->getDirection(), new Vector3(max(($x << 4) - $offset->getX(), $pos1->getX()) + $ox * $xSize, $pos1->getY(), max(($z << 4) - $offset->getZ(), $pos1->getZ()) + $oz * $zSize), new Vector3(min((($x + 1) << 4) + 15 - $offset->getX(), $pos2->getX()) + $ox * $xSize, $pos2->getY(), min((($z + 1) << 4) + 15 - $offset->getZ(), $pos2->getZ()) + $oz * $zSize), true, true);
						}
					}
				} else {
					$splitMinX = $this->direction->getX() === 0 ? $x : $min->getX() >> 4;
					$splitMaxX = $this->direction->getX() === 0 ? $x + 1 : $max->getX() >> 4;
					$splitMinZ = $this->direction->getZ() === 0 ? $z : $min->getZ() >> 4;
					$splitMaxZ = $this->direction->getZ() === 0 ? $z + 1 : $max->getZ() >> 4;
					for ($ox = $splitMinX; $ox <= $splitMaxX; $ox += 2) {
						for ($oz = $splitMinZ; $oz <= $splitMaxZ; $oz += 2) {
							$pieces[] = new StackedCube($this->getPlayer(), $this->getWorldName(), new Vector3(max(($x << 4) - $offset->getX(), $this->pos1->getX()), $this->pos1->getY(), max(($z << 4) - $offset->getZ(), $this->pos1->getZ())), new Vector3(min((($x + 1) << 4) + 15 - $offset->getX(), $this->pos2->getX()), $this->pos2->getY(), min((($z + 1) << 4) + 15 - $offset->getZ(), $this->pos2->getZ())), $this->getDirection(), new Vector3(max(($ox << 4) - $offset->getX(), $min->getX()), $min->getY(), max(($oz << 4) - $offset->getZ(), $min->getZ())), new Vector3(min((($ox + 1) << 4) + 15 - $offset->getX(), $max->getX()), $max->getY(), min((($oz + 1) << 4) + 15 - $offset->getZ(), $max->getZ())), true);
						}
					}
				}
			}
		}
		return $pieces;
	}
}