<?php

namespace platz1de\EasyEdit\selection\piece;

use Closure;
use platz1de\EasyEdit\selection\Sphere;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\utils\Utils;
use RuntimeException;

class SpherePiece extends Sphere
{
	/**
	 * @var Vector3
	 */
	protected $min;
	/**
	 * @var Vector3
	 */
	protected $max;

	/**
	 * SpherePiece constructor.
	 * @param string       $player
	 * @param string       $level
	 * @param Vector3|null $pos1
	 * @param Vector3|null $min
	 * @param Vector3|null $max
	 * @param int          $radius
	 */
	public function __construct(string $player, string $level = "", ?Vector3 $pos1 = null, ?Vector3 $min = null, ?Vector3 $max = null, int $radius = 0)
	{
		$this->min = $min;
		$this->max = $max;
		parent::__construct($player, $level, $pos1, $radius, true);
	}

	/**
	 * @return Vector3
	 */
	public function getCubicStart(): Vector3
	{
		return $this->min;
	}

	/**
	 * @return Vector3
	 */
	public function getCubicEnd(): Vector3
	{
		return $this->max;
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
		$radiusSquared = $radius ** 2;
		$minX = max($this->min->getX() - $this->pos1->getX(), -$radius);
		$maxX = min($this->max->getX() - $this->pos1->getX(), $radius);
		$minY = $this->min->getY() - $this->pos1->getY();
		$maxY = $this->max->getY() - $this->pos1->getY();
		$minZ = max($this->min->getZ() - $this->pos1->getZ(), -$radius);
		$maxZ = min($this->max->getZ() - $this->pos1->getZ(), $radius);
		for ($x = $minX; $x <= $maxX; $x++) {
			for ($z = $minZ; $z <= $maxZ; $z++) {
				for ($y = $minY; $y <= $maxY; $y++) {
					if (($x ** 2) + ($y ** 2) + ($z ** 2) <= $radiusSquared) {
						$closure($this->pos1->getX() + $x, $this->pos1->getY() + $y, $this->pos1->getZ() + $z);
					}
				}
			}
		}
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
			"miniX" => $this->min->getX(),
			"miniY" => $this->min->getY(),
			"miniZ" => $this->min->getZ(),
			"maxiX" => $this->max->getX(),
			"maxiY" => $this->max->getY(),
			"maxiZ" => $this->max->getZ(),
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
		$this->min = new Vector3($dat["miniX"], $dat["miniY"], $dat["miniZ"]);
		$this->max = new Vector3($dat["maxiX"], $dat["maxiY"], $dat["maxiZ"]);
	}
}