<?php

namespace platz1de\EasyEdit\selection;

use platz1de\EasyEdit\task\ReferencedChunkManager;
use pocketmine\block\Block;
use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Server;
use RuntimeException;

class StaticBlockListSelection extends BlockListSelection
{
	/**
	 * @var Vector3
	 */
	private $pos;

	/**
	 * UndoBlockListSelection constructor.
	 * @param string  $player
	 * @param string  $level
	 * @param Vector3 $pos
	 * @param int     $xSize
	 * @param int     $ySize
	 * @param int     $zSize
	 */
	public function __construct(string $player, string $level, Vector3 $pos, int $xSize, int $ySize, int $zSize)
	{
		parent::__construct($player, new ReferencedChunkManager($level), $xSize, $ySize, $zSize);
		$this->pos = $pos;
		$this->getManager()->load($pos, $xSize, $zSize);
	}

	/**
	 * @param Position $place
	 * @return array
	 */
	public function getNeededChunks(Position $place): array
	{
		$chunks = [];
		for ($x = $this->pos->getX() >> 4; $x <= ($this->pos->getX() + $this->getXSize()) >> 4; $x++) {
			for ($z = $this->pos->getZ() >> 4; $z <= ($this->pos->getZ() + $this->getZSize()) >> 4; $z++) {
				$this->getManager()->getLevel()->loadChunk($x, $z);
				$chunks[] = $this->getManager()->getLevel()->getChunk($x, $z);
			}
		}
		return $chunks;
	}

	/**
	 * @return Vector3
	 */
	public function getPos(): Vector3
	{
		return $this->pos;
	}

	/**
	 * @return string
	 */
	public function serialize(): string
	{
		return igbinary_serialize([
			"player" => $this->player,
			"x" => $this->pos->getX(),
			"y" => $this->pos->getY(),
			"z" => $this->pos->getZ(),
			"xSize" => $this->getXSize(),
			"ySize" => $this->getYSize(),
			"zSize" => $this->getZSize(),
			"chunks" => array_map(static function (Chunk $chunk) {
				return $chunk->fastSerialize();
			}, $this->getManager()->getChunks()),
			"level" => $this->getManager()->getLevelName()
		]);
	}

	/**
	 * @param string $serialized
	 */
	public function unserialize($serialized): void
	{
		$data = igbinary_unserialize($serialized);
		$this->pos = new Vector3($data["x"], $data["y"], $data["z"]);
		parent::unserialize($serialized);
	}
}