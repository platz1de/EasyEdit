<?php

namespace platz1de\EasyEdit\selection;

use platz1de\EasyEdit\selection\cubic\CubicChunkLoader;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\math\Vector3;
use pocketmine\Server;
use RuntimeException;

class StackedCube extends Cube
{
	use CubicChunkLoader;

	/**
	 * @var Vector3
	 */
	private $direction;

	public function __construct(Cube $cube, Vector3 $direction)
	{
		parent::__construct($cube->getPlayer(), is_string($cube->level) ? $cube->level : $cube->level->getFolderName(), $cube->getPos1(), $cube->getPos2());
		$this->direction = $direction;
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
	 * @return array
	 */
	public function getData(): array
	{
		return array_merge([
			"directionX" => $this->direction->getX(),
			"directionY" => $this->direction->getY(),
			"directionZ" => $this->direction->getZ()
		], parent::getData());
	}

	/**
	 * @param array $data
	 */
	public function setData(array $data): void
	{
		$this->direction = new Vector3($data["directionX"], $data["directionY"], $data["directionZ"]);
		parent::setData($data);
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