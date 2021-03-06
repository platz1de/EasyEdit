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

class StackedCube extends Cube
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
		$start = VectorUtils::getMin($this->getCubicStart(), $this->pos1);
		$size = $this->getRealSize()->add(parent::getRealSize());
		for ($x = $start->getX() >> 4; $x <= $start->getX() + $size->getX() >> 4; $x++) {
			for ($z = $start->getZ() >> 4; $z <= $start->getZ() + $size->getZ() >> 4; $z++) {
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
		$start = VectorUtils::enforceHeight($this->getCubicStart());
		$end = VectorUtils::enforceHeight($this->getCubicStart()->add($this->getRealSize())->subtract(1, 1, 1));
		for ($x = $start->getX(); $x <= $end->getX(); $x++) {
			for ($z = $start->getZ(); $z <= $end->getZ(); $z++) {
				for ($y = $start->getY(); $y <= $end->getY(); $y++) {
					$closure($x, $y, $z);
				}
			}
		}
	}

	/**
	 * @return Vector3
	 */
	public function getRealSize(): Vector3
	{
		return VectorUtils::multiply($this->direction->abs()->subtract($this->direction->abs()->normalize())->add(1, 1, 1), parent::getRealSize());
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
		return VectorUtils::getMin($this->getPos1()->add(VectorUtils::multiply($this->getDirection()->normalize(), parent::getRealSize())), $this->getPos1()->add(VectorUtils::multiply($this->getDirection(), parent::getRealSize())));
	}

	/**
	 * @return array
	 */
	public function split(): array
	{
		return Selection::split();
	}
}