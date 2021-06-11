<?php

namespace platz1de\EasyEdit\selection;

use platz1de\EasyEdit\selection\cubic\CubicChunkLoader;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\math\Vector3;
use pocketmine\utils\BinaryStream;

class StackedCube extends Cube
{
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

	public function update(): void
	{
		Selection::update();
	}

	public function close(): void
	{
		Selection::close();
	}

	/**
	 * @return Vector3
	 */
	public function getDirection(): Vector3
	{
		return $this->direction;
	}

	/**
	 * @param BinaryStream $stream
	 */
	public function putData(BinaryStream $stream): void
	{
		parent::putData($stream);

		$stream->putInt($this->direction->getX());
		$stream->putInt($this->direction->getY());
		$stream->putInt($this->direction->getZ());
	}

	/**
	 * @param BinaryStream $stream
	 */
	public function parseData(BinaryStream $stream): void
	{
		parent::parseData($stream);

		$this->direction = new Vector3($stream->getInt(), $stream->getInt(), $stream->getInt());
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
	 * @return array
	 */
	public function split(): array
	{
		//TODO
		return Selection::split();
	}
}