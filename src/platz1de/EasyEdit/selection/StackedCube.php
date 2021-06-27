<?php

namespace platz1de\EasyEdit\selection;

use platz1de\EasyEdit\selection\cubic\CubicChunkLoader;
use platz1de\EasyEdit\selection\cubic\CubicIterator;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\level\Position;
use pocketmine\math\Vector3;

class StackedCube extends Selection
{
	use CubicIterator;
	use CubicChunkLoader;

	/**
	 * @var Vector3
	 */
	private $direction;

	/**
	 * StackedCube constructor.
	 * @param string       $player
	 * @param string       $level
	 * @param Vector3|null $pos1
	 * @param Vector3|null $pos2
	 * @param Vector3|null $direction
	 */
	public function __construct(string $player, string $level = "", ?Vector3 $pos1 = null, ?Vector3 $pos2 = null, ?Vector3 $direction = null)
	{
		parent::__construct($player, $level, $pos1, $pos2);
		$this->direction = $direction ?? new Vector3(0, 0, 0);
	}

	/**
	 * @return Vector3
	 */
	public function getDirection(): Vector3
	{
		return $this->direction;
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
		return VectorUtils::getMin($this->getPos1()->add(VectorUtils::multiply($this->getDirection()->normalize(), $this->getSize())), $this->getPos1()->add(VectorUtils::multiply($this->getDirection(), $this->getSize())));
	}

	/**
	 * @return Vector3
	 */
	public function getCubicEnd(): Vector3
	{
		return VectorUtils::getMax($this->getPos2()->add(VectorUtils::multiply($this->getDirection()->normalize(), $this->getSize())), $this->getPos2()->add(VectorUtils::multiply($this->getDirection(), $this->getSize())));
	}

	/**
	 * @param Vector3 $offset
	 * @return Selection[]
	 */
	public function split(Vector3 $offset): array
	{
		//TODO
		return parent::split($offset);
	}
}