<?php

namespace platz1de\EasyEdit\selection;

use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Server;
use RuntimeException;

class Cube extends Selection
{
	/**
	 * @var Vector3
	 */
	private $pos1;
	/**
	 * @var Vector3
	 */
	private $pos2;

	/**
	 * Cube constructor.
	 * @param string  $player
	 * @param Level   $level
	 * @param Vector3 $pos1
	 * @param Vector3 $pos2
	 */
	public function __construct(string $player, Level $level, Vector3 $pos1, Vector3 $pos2)
	{
		parent::__construct($player, $level);

		self::getEdges($pos1, $pos2);

		$this->pos1 = $pos1;
		$this->pos2 = $pos2;
	}

	/**
	 * @return Vector3[]
	 */
	public function getAffectedBlocks(): array
	{
		$blocks = [];
		for ($x = $this->pos1->getX(); $x <= $this->pos2->getX(); $x++) {
			for ($y = $this->pos1->getY(); $y <= $this->pos2->getY(); $y++) {
				for ($z = $this->pos1->getZ(); $z <= $this->pos2->getZ(); $z++) {
					$blocks[] = new Vector3($x, $y, $z);
				}
			}
		}
		return $blocks;
	}

	/**
	 * @param Vector3 $pos1
	 * @param Vector3 $pos2
	 */
	public static function getEdges(Vector3 $pos1, Vector3 $pos2): void
	{
		$minX = min($pos1->getX(), $pos2->getX());
		$maxX = max($pos1->getX(), $pos2->getX());
		$minY = min($pos1->getX(), $pos2->getX());
		$maxY = max($pos1->getX(), $pos2->getX());
		$minZ = min($pos1->getX(), $pos2->getX());
		$maxZ = max($pos1->getX(), $pos2->getX());

		$pos1->setComponents($minX, $minY, $minZ);
		$pos2->setComponents($maxX, $maxY, $maxZ);
	}

	/**
	 * @return Chunk[]
	 */
	public function getNeededChunks(): array
	{
		$chunks = [];
		for ($x = $this->pos1->getX() >> 4; $x <= $this->pos2->getX() >> 4; $x++) {
			for ($z = $this->pos1->getZ() >> 4; $z <= $this->pos2->getZ() >> 4; $z++) {
				$this->getLevel()->loadChunk($x, $z);
				$chunks[] = $this->getLevel()->getChunk($x, $z);
			}
		}
		return $chunks;
	}

	/**
	 * @return string
	 */
	public function serialize(): string
	{
		return igbinary_serialize([
			"player" => $this->player,
			"level" => is_string($this->level) ? $this->level : $this->level->getName(),
			"minX" => $this->pos1->getX(),
			"minY" => $this->pos1->getY(),
			"minZ" => $this->pos1->getZ(),
			"maxX" => $this->pos2->getX(),
			"maxY" => $this->pos2->getY(),
			"maxZ" => $this->pos2->getZ()
		]);
	}

	public function unserialize($serialized): void
	{
		$data = igbinary_unserialize($serialized);
		$this->player = $data["player"];
		try {
			$this->level = Server::getInstance()->getLevelByName($data["level"]);
		} catch (RuntimeException $exception) {
			$this->level = $data["level"];
		}
		$this->pos1 = new Vector3($data["minX"], $data["minY"], $data["minZ"]);
		$this->pos2 = new Vector3($data["maxX"], $data["maxY"], $data["maxZ"]);
	}
}