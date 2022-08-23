<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\math\Vector3;
use pocketmine\utils\Utils;
use pocketmine\world\World;

class MovingCube extends Selection
{
	private Vector3 $direction;

	/**
	 * MovingCube constructor.
	 * @param string  $world
	 * @param Vector3 $pos1
	 * @param Vector3 $pos2
	 * @param Vector3 $direction
	 */
	public function __construct(string $world, Vector3 $pos1, Vector3 $pos2, Vector3 $direction)
	{
		parent::__construct($world, $pos1, $pos2);
		$this->direction = $direction;
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
		//TODO: This is by no means optimized (tons of chunks get loaded multiple times)
		$min = Vector3::maxComponents($min, $this->pos1)->addVector($this->direction);
		$max = Vector3::minComponents($max, $this->pos2)->addVector($this->direction);
		$chunks = [];
		for ($x = $min->getX() >> 4; $x <= $max->getX() >> 4; $x++) {
			for ($z = $min->getZ() >> 4; $z <= $max->getZ() >> 4; $z++) {
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
	public function shouldBeCached(int $x, int $z): bool
	{
		return false; //no overlapping chunks
	}

	/**
	 * @param Closure          $closure
	 * @param SelectionContext $context
	 * @param Selection        $full
	 * @param Vector3          $min
	 * @param Vector3          $max
	 */
	public function useOnBlocks(Closure $closure, SelectionContext $context, Selection $full, Vector3 $min, Vector3 $max): void
	{
		Utils::validateCallableSignature(static function (int $x, int $y, int $z): void { }, $closure);
		$min = VectorUtils::enforceHeight(Vector3::maxComponents($this->getPos1(), $min));
		$max = VectorUtils::enforceHeight(Vector3::minComponents($this->getPos2(), $max));
		for ($this->direction->getX() > 0 ? $x = $max->getX() : $x = $min->getX(); $this->direction->getX() > 0 ? $x >= $min->getX() : $x <= $max->getX(); $this->direction->getX() > 0 ? $x-- : $x++) {
			for ($this->direction->getZ() > 0 ? $z = $max->getZ() : $z = $min->getZ(); $this->direction->getZ() > 0 ? $z >= $min->getZ() : $z <= $max->getZ(); $this->direction->getZ() > 0 ? $z-- : $z++) {
				for ($this->direction->getY() > 0 ? $y = $max->getY() : $y = $min->getY(); $this->direction->getY() > 0 ? $y >= $min->getY() : $y <= $max->getY(); $this->direction->getY() > 0 ? $y-- : $y++) {
					$closure($x, $y, $z);
				}
			}
		}
	}

	/**
	 * @return Vector3
	 */
	public function getCubicStart(): Vector3
	{
		return Vector3::minComponents($this->getPos1(), $this->getPos1()->addVector($this->direction));
	}

	/**
	 * @return Vector3
	 */
	public function getCubicEnd(): Vector3
	{
		//TODO: don't add all blocks in between the positions
		return Vector3::maxComponents($this->getPos2(), $this->getPos2()->addVector($this->direction));
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
}