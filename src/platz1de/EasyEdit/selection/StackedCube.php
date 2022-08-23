<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class StackedCube extends Selection
{
	private Vector3 $direction;
	private Vector3 $min;
	private Vector3 $max;
	private bool $copyMode;

	/**
	 * StackedCube constructor.
	 * @param string       $world
	 * @param Vector3      $pos1
	 * @param Vector3      $pos2
	 * @param Vector3      $direction
	 * @param Vector3|null $min
	 * @param Vector3|null $max
	 * @param bool         $copyMode
	 */
	public function __construct(string $world, Vector3 $pos1, Vector3 $pos2, Vector3 $direction, ?Vector3 $min = null, ?Vector3 $max = null, bool $copyMode = false)
	{
		parent::__construct($world, $pos1, $pos2);
		$this->direction = $direction;
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
		return $chunks;
	}

	/**
	 * @param Vector3 $min
	 * @param Vector3 $max
	 * @return int[]
	 */
	public function getReferencedChunks(Vector3 $min, Vector3 $max): array
	{
		//TODO
		return [];
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
	 * @param Vector3          $min
	 * @param Vector3          $max
	 */
	public function useOnBlocks(Closure $closure, SelectionContext $context, Vector3 $min, Vector3 $max): void
	{
		//TODO (this should be moved to a task)
		//CubicConstructor::betweenPoints(Vector3::maxComponents($this->min, $min), Vector3::minComponents($this->max, $max), $closure);
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
}