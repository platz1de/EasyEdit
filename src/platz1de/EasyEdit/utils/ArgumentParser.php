<?php

namespace platz1de\EasyEdit\utils;

use platz1de\EasyEdit\pattern\parser\ParseError;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

class ArgumentParser
{
	public static function parseCoordinates(Player $player, string $x, string $y, string $z): Vector3
	{
		return self::parseCoordinatesNonRounded($player, $x, $y, $z)->floor();
	}

	/**
	 * In most cases, you want the normal parseCoordinates instead (worldedit normally uses blocks when editing so...)
	 */
	public static function parseCoordinatesNonRounded(Player $player, string $x, string $y, string $z): Vector3
	{
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
}