<?php

namespace platz1de\EasyEdit\utils;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\exception\InvalidUsageException;
use platz1de\EasyEdit\math\OffGridBlockVector;
use platz1de\EasyEdit\pattern\parser\ParseError;
use platz1de\EasyEdit\session\Session;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;

class ArgumentParser
{
	public static function parseCoordinates(Session $session, string $x, string $y, string $z): Vector3
	{
		return self::parseCoordinatesNonRounded($session, $x, $y, $z)->floor();
	}

	/**
	 * In most cases, you want the normal parseCoordinates instead (worldedit normally uses blocks when editing so...)
	 */
	public static function parseCoordinatesNonRounded(Session $session, string $x, string $y, string $z): Vector3
	{
		$player = $session->asPlayer();
		if ($x !== "" && $x[0] === "^") {
			//really weird directional coordinates, this essentially rotates the whole coordinate system
			if (($y[0] ?? "") !== "^" || ($z[0] ?? "") !== "^") {
				throw new ParseError("Invalid directional coordinates");
			}
			$d = $player->getDirectionVector();
			return $player->getEyePos()
				->addVector((new Vector3(-$d->getZ(), 0, $d->getX()))->normalize()->multiply((float) substr($x, 1)))
				->addVector($player->getDirectionVector()->cross(new Vector3($d->getZ(), 0, -$d->getX()))->normalize()->multiply((float) substr($y, 1)))
				->addVector($player->getDirectionVector()->multiply((float) substr($z, 1)));
		}
		return new Vector3(self::parseCoordinate($player->getPosition()->getX(), $x), self::parseCoordinate($player->getPosition()->getY(), $y), self::parseCoordinate($player->getPosition()->getZ(), $z));
	}

	private static function parseCoordinate(float $player, string $coordinate): float
	{
		if ($coordinate === "") {
			return 0;
		}

		if ($coordinate[0] === "~") {
			return $player + (float) substr($coordinate, 1);
		}
		return (float) $coordinate;
	}

	/**
	 * @param Session     $session
	 * @param string|null $args1
	 * @param string|null $args2
	 * @param int|null    $amount
	 * @return OffGridBlockVector
	 */
	public static function parseDirectionVector(Session $session, string $args1 = null, string $args2 = null, int &$amount = null): OffGridBlockVector
	{
		$amount = 1;
		if (is_numeric($args1)) {
			$amount = (int) $args1;
			$direction = $args2;
		} else {
			$direction = $args1;
			if (is_numeric($args2)) {
				$amount = (int) $args2;
			}
		}
		return OffGridBlockVector::zero()->getSide(self::parseFacing($session, $direction), $amount);
	}

	/**
	 * @param Session     $session
	 * @param string|null $direction
	 * @return int
	 */
	public static function parseFacing(Session $session, string $direction = null): int
	{
		return match (strtolower($direction ?? "")) {
			"north", "n" => Facing::NORTH,
			"south", "s" => Facing::SOUTH,
			"east", "e" => Facing::EAST,
			"west", "w" => Facing::WEST,
			"up", "u" => Facing::UP,
			"down", "d" => Facing::DOWN,
			default => VectorUtils::getFacing($session->asPlayer()->getLocation())
		};
	}
}