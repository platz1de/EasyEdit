<?php

namespace platz1de\EasyEdit\selection;

use platz1de\EasyEdit\task\ReferencedChunkManager;
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
	 * @param Vector3 $relativePlace
	 * @param int     $xSize
	 * @param int     $ySize
	 * @param int     $zSize
	 */
	public function __construct(string $player, Vector3 $relativePlace, int $xSize, int $ySize, int $zSize)
	{
		parent::__construct($player, new ReferencedChunkManager(""), $xSize, $ySize, $zSize);
		$this->getManager()->load(new Vector3(), $xSize, $zSize);
		$this->point = $relativePlace;
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
	 * @return string
	 */
	public function serialize(): string
	{
		return igbinary_serialize([
			"player" => $this->player,
			"x" => $this->point->getX(),
			"y" => $this->point->getY(),
			"z" => $this->point->getZ(),
			"xSize" => $this->getXSize(),
			"ySize" => $this->getYSize(),
			"zSize" => $this->getZSize(),
			"chunks" => array_map(static function (Chunk $chunk) {
				return $chunk->fastSerialize();
			}, $this->getManager()->getChunks()),
			"level" => $this->getManager()->getLevelName(),
			"tiles" => $this->getTiles()
		]);
	}

	/**
	 * @param string $serialized
	 * @noinspection PhpMissingParamTypeInspection
	 */
	public function unserialize($serialized): void
	{
		$data = igbinary_unserialize($serialized);
		$this->point = new Vector3($data["x"], $data["y"], $data["z"]);
		parent::unserialize($serialized);
	}
}