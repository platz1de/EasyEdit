<?php

namespace platz1de\EasyEdit\utils;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\exception\InvalidUsageException;
use platz1de\EasyEdit\command\exception\NoClipboardException;
use platz1de\EasyEdit\command\exception\NoSelectionException;
use platz1de\EasyEdit\command\exception\PatternParseException;
use platz1de\EasyEdit\command\exception\WrongSelectionTypeException;
use platz1de\EasyEdit\pattern\parser\ParseError;
use platz1de\EasyEdit\pattern\parser\PatternParser;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\ClipBoardManager;
use platz1de\EasyEdit\selection\Cube;
use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionManager;
use pocketmine\math\Facing;
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
	 * @param Player      $player
	 * @param string|null $args
	 * @return Vector3
	 */
	public static function parseRelativePosition(Player $player, string $args = null): Vector3
	{
		return match ($args) {
			"center", "c", "middle" => self::getSelection($player)->getBottomCenter(),
			default => $player->getPosition()
		};
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
	 * @return StoredSelectionIdentifier
	 */
	public static function getClipboard(Player $player): StoredSelectionIdentifier
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
	 * @param string[]    $args
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

	/**
	 * @param Player      $player
	 * @param string|null $args1
	 * @param string|null $args2
	 * @param int|null    $amount
	 * @return Vector3
	 */
	public static function parseDirectionVector(Player $player, string $args1 = null, string $args2 = null, int &$amount = null): Vector3
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
		return Vector3::zero()->getSide(self::parseFacing($player, $direction), $amount);
	}

	/**
	 * @param Player      $player
	 * @param string|null $direction
	 * @return int
	 */
	public static function parseFacing(Player $player, string $direction = null): int
	{
		return match ($direction) {
			"north", "n" => Facing::NORTH,
			"south", "s" => Facing::SOUTH,
			"east", "e" => Facing::EAST,
			"west", "w" => Facing::WEST,
			"up", "u" => Facing::UP,
			"down", "d" => Facing::DOWN,
			default => VectorUtils::getFacing($player->getLocation())
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
		return match ($argument) {
			"true", "t", "yes", "y", "1", "+" => true,
			"false", "f", "no", "n", "0", "-" => false,
			default => $default
		};
	}
}