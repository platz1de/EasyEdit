<?php

namespace platz1de\EasyEdit\selection;

use pocketmine\level\format\Chunk;
use pocketmine\level\Position;
use pocketmine\math\Vector3;

class DynamicBlockListSelection extends BlockListSelection
{
	/**
	 * @var Vector3
	 */
	private $point;

	/**
	 * DynamicBlockListSelection constructor.
	 * @param string  $player
	 * @param Vector3 $place
	 * @param Vector3 $pos1
	 * @param Vector3 $pos2
	 */
	public function __construct(string $player, Vector3 $place, Vector3 $pos1, Vector3 $pos2)
	{
		parent::__construct($player, "", new Vector3(), $pos2->subtract($pos1));
		$this->point = $place->subtract($pos1);
	}

	/**
	 * @param Position $place
	 * @return Chunk[]
	 */
	public function getNeededChunks(Position $place): array
	{
		return parent::getNeededChunks(Position::fromObject($place->subtract($this->getPoint()), $place->getLevel()));
	}

	/**
	 * @return Vector3
	 */
	public function getPoint(): Vector3
	{
		return $this->point;
	}

	/**
	 * @return array
	 */
	public function getData(): array
	{
		return array_merge([
			"x" => $this->point->getX(),
			"y" => $this->point->getY(),
			"z" => $this->point->getZ()
		], parent::getData());
	}

	/**
	 * @param array $data
	 */
	public function setData(array $data): void
	{
		$this->point = new Vector3($data["x"], $data["y"], $data["z"]);
		parent::setData($data);
	}
}