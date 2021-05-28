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
	 * @return string
	 */
	public function serialize(): string
	{
		return igbinary_serialize([
			"player" => $this->player,
			"level" => is_string($this->level) ? $this->level : $this->level->getFolderName(),
			"minX" => $this->pos1->getX(),
			"minY" => $this->pos1->getY(),
			"minZ" => $this->pos1->getZ(),
			"maxX" => $this->pos2->getX(),
			"maxY" => $this->pos2->getY(),
			"maxZ" => $this->pos2->getZ(),
			"directionX" => $this->direction->getX(),
			"directionY" => $this->direction->getY(),
			"directionZ" => $this->direction->getZ()
		]);
	}

	/**
	 * @param string $data
	 */
	public function unserialize($data): void
	{
		$dat = igbinary_unserialize($data);
		$this->player = $dat["player"];
		try {
			$this->level = Server::getInstance()->getLevelByName($dat["level"]) ?? $dat["level"];
		} catch (RuntimeException $exception) {
			$this->level = $dat["level"];
		}
		$this->pos1 = new Vector3($dat["minX"], $dat["minY"], $dat["minZ"]);
		$this->pos2 = new Vector3($dat["maxX"], $dat["maxY"], $dat["maxZ"]);
		$this->direction = new Vector3($dat["directionX"], $dat["directionY"], $dat["directionZ"]);
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
		return Selection::split();
	}
}