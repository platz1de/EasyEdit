<?php

namespace platz1de\EasyEdit\utils;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\exception\InvalidUsageException;
use platz1de\EasyEdit\command\exception\PatternParseException;
use platz1de\EasyEdit\pattern\parser\ParseError;
use platz1de\EasyEdit\pattern\parser\PatternParser;
use platz1de\EasyEdit\pattern\Pattern;
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
	 * @return Vector3
	 */
	public static function parseDirectionVector(Session $session, string $args1 = null, string $args2 = null, int &$amount = null): Vector3
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
		return Vector3::zero()->getSide(self::parseFacing($session, $direction), $amount);
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

	/**
	 * @param string[]        $args
	 * @param int             $count
	 * @param EasyEditCommand $command
	 */
	public static function requireArgumentCount(array $args, int $count, EasyEditCommand $command): void
	{
		if (count($args) < $count) {
			throw new InvalidUsageException($command);
		}
	}

	/**
	 * @param bool        $default
	 * @param string|null $argument
	 * @return bool
	 */
	public static function parseBool(bool $default, string $argument = null): bool
	{
		return match (strtolower($argument ?? "")) {
			"true", "t", "yes", "y", "1", "+" => true,
			"false", "f", "no", "n", "0", "-" => false,
			default => $default
		};
	}
}