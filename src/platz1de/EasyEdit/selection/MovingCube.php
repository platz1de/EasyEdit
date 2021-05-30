<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use platz1de\EasyEdit\utils\LoaderManager;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\utils\Utils;
use RuntimeException;

class MovingCube extends Cube
{
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
	 * @param Position $place
	 * @return array
	 */
	public function getNeededChunks(Position $place): array
	{
		$chunks = [];
		//TODO: Remove duplicates
		for ($x = $this->pos1->getX() >> 4; $x <= $this->pos2->getX() >> 4; $x++) {
			for ($z = $this->pos1->getZ() >> 4; $z <= $this->pos2->getZ() >> 4; $z++) {
				$chunks[] = LoaderManager::getChunk($this->getLevel(), $x, $z);
			}
		}
		for ($x = ($this->pos1->getX() + $this->direction->getX()) >> 4; $x <= ($this->pos2->getX() + $this->direction->getX()) >> 4; $x++) {
			for ($z = ($this->pos1->getZ() + $this->direction->getZ()) >> 4; $z <= ($this->pos2->getZ() + $this->direction->getZ()) >> 4; $z++) {
				$chunks[] = LoaderManager::getChunk($this->getLevel(), $x, $z);
			}
		}
		return $chunks;
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
		$min = VectorUtils::enforceHeight($this->pos1);
		$max = VectorUtils::enforceHeight($this->pos2);
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
		return VectorUtils::getMin($this->getPos1(), $this->getPos1()->add($this->direction));
	}

	/**
	 * @return Vector3
	 */
	public function getCubicEnd(): Vector3
	{
		//TODO: don't add all blocks in between the positions
		return VectorUtils::getMax($this->getPos2(), $this->getPos2()->add($this->direction));
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
	 * @return array
	 */
	public function split(): array
	{
		//TODO
		return Selection::split();
	}
}