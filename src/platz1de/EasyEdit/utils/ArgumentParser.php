<?php

namespace platz1de\EasyEdit\utils;

use platz1de\EasyEdit\command\exception\NoClipboardException;
use platz1de\EasyEdit\command\exception\NoSelectionException;
use platz1de\EasyEdit\command\exception\PatternParseException;
use platz1de\EasyEdit\command\exception\WrongSelectionTypeException;
use platz1de\EasyEdit\pattern\parser\ParseError;
use platz1de\EasyEdit\pattern\parser\PatternParser;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\ClipBoardManager;
use platz1de\EasyEdit\selection\Cube;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionManager;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use Throwable;

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

	/**
	 * @param Player $player
	 * @return Selection
	 */
	public static function getSelection(Player $player): Selection
	{
		try {
			$selection = SelectionManager::getFromPlayer($player->getName());
		} catch (Throwable) {
			throw new NoSelectionException();
		}
		if (!$selection->isValid()) {
			throw new NoSelectionException();
		}
		return $selection;
	}

	/**
	 * @param Player $player
	 * @return Cube
	 */
	public static function getCube(Player $player): Cube
	{
		$selection = self::getSelection($player);
		if (!$selection instanceof Cube) {
			throw new WrongSelectionTypeException($selection::class, Cube::class);
		}
		return $selection;
	}

	/**
	 * @param Player $player
	 * @return int
	 */
	public static function getClipboard(Player $player): int
	{
		try {
			$clipboard = ClipBoardManager::getFromPlayer($player->getName());
		} catch (Throwable) {
			throw new NoClipboardException();
		}
		return $clipboard;
	}

	/**
	 * @param Player      $player
	 * @param array       $args
	 * @param int         $start
	 * @param string|null $default
	 * @return Pattern
	 */
	public static function parseCombinedPattern(Player $player, array $args, int $start, string $default = null): Pattern
	{
		try {
			return PatternParser::parseInputCombined($args, $start, $player, $default);
		} catch (ParseError $exception) {
			throw new PatternParseException($exception);
		}
	}
}