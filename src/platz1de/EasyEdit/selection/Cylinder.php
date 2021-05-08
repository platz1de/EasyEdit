<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use platz1de\EasyEdit\utils\LoaderManager;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\utils\Utils;
use RuntimeException;

class Cylinder extends Selection
{
	/**
	 * Cylinder constructor.
	 * @param string       $player
	 * @param string       $level
	 * @param Vector3|null $pos1
	 * @param int          $radius
	 * @param int          $height
	 * @param bool         $piece
	 */
	public function __construct(string $player, string $level = "", ?Vector3 $pos1 = null, int $radius = 0, int $height = 0, bool $piece = false)
	{
		$pos2 = new Vector3($radius, $height); //This is not optimal, but currently needed...
		parent::__construct($player, $level, $pos1, $pos2, $piece);
	}

	/**
	 * @param Position $place
	 * @return Chunk[]
	 */
	public function getNeededChunks(Position $place): array
	{
		$radius = $this->pos2->getX();
		$chunks = [];
		//TODO: yea this is a square (change this)
		for ($x = ($this->pos1->getX() - $radius - 1) >> 4; $x <= ($this->pos1->getX() + $radius + 1) >> 4; $x++) {
			for ($z = ($this->pos1->getZ() - $radius - 1) >> 4; $z <= ($this->pos1->getZ() + $radius + 1) >> 4; $z++) {
				$chunks[] = LoaderManager::getChunk($this->getLevel(), $x, $z);
			}
		}
		return $chunks;
	}

	/**
	 * @return Vector3
	 */
	public function getRealSize(): Vector3
	{
		return new Vector3($this->getRadius() * 2 + 1, $this->getHeight(), $this->getRadius() * 2 + 1);
	}

	/**
	 * @return Vector3
	 */
	public function getCubicStart(): Vector3
	{
		return $this->getPos1()->subtract($this->getRadius(), 0, $this->getRadius());
	}

	/**
	 * @param Vector3 $place
	 * @param Closure $closure
	 * @return void
	 * @noinspection StaticClosureCanBeUsedInspection
	 */
	public function useOnBlocks(Vector3 $place, Closure $closure): void
	{
		Utils::validateCallableSignature(function (int $x, int $y, int $z): void { }, $closure);
		$radius = $this->pos2->getX();
		$height = $this->pos2->getY();
		$radiusSquared = $radius ** 2;
		$min = VectorUtils::enforceHeight($this->pos1);
		$maxY = min($this->pos1->getY() + $height - 1, Level::Y_MASK);
		for ($x = -$radius; $x <= $radius; $x++) {
			for ($z = -$radius; $z <= $radius; $z++) {
				for ($y = $min->getY(); $y <= $maxY; $y++) {
					if (($x ** 2) + ($z ** 2) <= $radiusSquared) {
						$closure($this->pos1->getX() + $x, $y, $this->pos1->getZ() + $z);
					}
				}
			}
		}
	}

	protected function update(): void
	{
		// don't mess everything up
	}

	/**
	 * @param Vector3 $pos
	 */
	public function setPos(Vector3 $pos): void
	{
		$this->pos1 = $pos;
	}

	/**
	 * @param int $radius
	 */
	public function setRadius(int $radius): void
	{
		$this->pos2->x = $radius;
	}

	/**
	 * @return int
	 */
	public function getRadius(): int
	{
		return $this->pos2->getX();
	}

	/**
	 * @param int $radius
	 */
	public function setHeight(int $radius): void
	{
		$this->pos2->y = $radius;
	}

	/**
	 * @return int
	 */
	public function getHeight(): int
	{
		return $this->pos2->getY();
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
			"maxZ" => $this->pos2->getZ()
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
	}
}