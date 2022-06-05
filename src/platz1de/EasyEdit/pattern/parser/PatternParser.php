<?php

namespace platz1de\EasyEdit\pattern\parser;

use platz1de\EasyEdit\pattern\block\BlockType;
use platz1de\EasyEdit\pattern\block\DynamicBlock;
use platz1de\EasyEdit\pattern\block\SolidBlock;
use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\pattern\functional\GravityPattern;
use platz1de\EasyEdit\pattern\functional\NaturalizePattern;
use platz1de\EasyEdit\pattern\logic\math\DivisiblePattern;
use platz1de\EasyEdit\pattern\logic\math\EvenPattern;
use platz1de\EasyEdit\pattern\logic\math\OddPattern;
use platz1de\EasyEdit\pattern\logic\NotPattern;
use platz1de\EasyEdit\pattern\logic\relation\AbovePattern;
use platz1de\EasyEdit\pattern\logic\relation\AroundPattern;
use platz1de\EasyEdit\pattern\logic\relation\BelowPattern;
use platz1de\EasyEdit\pattern\logic\relation\BlockPattern;
use platz1de\EasyEdit\pattern\logic\relation\EmbedPattern;
use platz1de\EasyEdit\pattern\logic\relation\HorizontalPattern;
use platz1de\EasyEdit\pattern\logic\selection\CenterPattern;
use platz1de\EasyEdit\pattern\logic\selection\SidesPattern;
use platz1de\EasyEdit\pattern\logic\selection\WallPattern;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\pattern\PatternConstruct;
use platz1de\EasyEdit\pattern\PatternWrapper;
use platz1de\EasyEdit\pattern\type\AxisArgumentWrapper;
use platz1de\EasyEdit\utils\BlockParser;
use pocketmine\player\Player;

class PatternParser
{
	/**
	 * Parses player input (mostly commands)
	 * @param string $pattern
	 * @param Player $player
	 * @return string
	 */
	public static function validateInput(string $pattern, Player $player): string
	{
		self::parseInput($pattern, $player);
		return str_replace("hand", $player->getInventory()->getItemInHand()->getBlock()->getId() . ":" . $player->getInventory()->getItemInHand()->getBlock()->getMeta(), $pattern);
	}

	/**
	 * Parses player input (mostly commands)
	 * @param string $pattern
	 * @param Player $player
	 * @return Pattern
	 */
	public static function parseInput(string $pattern, Player $player): Pattern
	{
		return self::parseInternal(str_replace("hand", $player->getInventory()->getItemInHand()->getBlock()->getId() . ":" . $player->getInventory()->getItemInHand()->getBlock()->getMeta(), $pattern));
	}

	/**
	 * Parses player input (mostly commands), allows spaces
	 * @param string[]    $args
	 * @param int         $start
	 * @param Player      $player
	 * @param string|null $default
	 * @return Pattern
	 */
	public static function parseInputCombined(array $args, int $start, Player $player, string $default = null): Pattern
	{
		$pattern = implode("", array_slice($args, $start));
		if ($pattern === "") {
			if ($default === null) {
				throw new ParseError("No pattern given");
			}
			$pattern = $default;
		}
		return self::parseInput($pattern, $player);
	}

	/**
	 * Parses internal saved patterns
	 * @param string $pattern
	 * @return Pattern
	 */
	public static function parseInternal(string $pattern): Pattern
	{
		if (!(bool) preg_match("/[^()]*(?:\((?R)\)(?R))?[^()]*/", $pattern, $test) || $test[0] !== $pattern) {
			throw new ParseError("Pattern contains incomplete brackets");
		}
		//basically magic
		preg_match_all("/(?:\((?:[^()]+|(?R))+\)|[^(),\s]+)+/", $pattern, $matches);
		$pieces = [];
		foreach ($matches[0] as $piece) {
			$pieces[] = self::parseLogical($piece);
		}
		return PatternWrapper::wrap($pieces);
	}

	/**
	 * @param string $pattern
	 * @return Pattern
	 */
	private static function parseLogical(string $pattern): Pattern
	{
		//more magic
		preg_match_all("/(?:\((?:[^()]+|(?R))+\)|[^().\s]+)+/", $pattern, $matches);
		$pieces = [];
		try {
			foreach ($matches[0] as $piece) {
				$pieces[] = self::parsePiece($piece);
			}
		} catch (ParseError $exception) {
			throw new ParseError('Failed to parse piece "' . $pattern . '"' . PHP_EOL . " - " . $exception->getMessage(), false);
		}
		return PatternConstruct::wrap($pieces);
	}

	/**
	 * @param string $input
	 * @return Pattern
	 */
	private static function parsePiece(string $input): Pattern
	{
		preg_match("/(?:([^%(]*)%)?(.*)/", $input, $weightData, PREG_UNMATCHED_AS_NULL);

		$weight = $weightData[1] ?? 100;
		$patternString = $weightData[2];

		if ($patternString === "") {
			throw new ParseError("No pattern given");
		}

		if (!is_numeric($weight)) {
			throw new ParseError("Invalid pattern weight");
		}

		//blocks have priority
		try {
			$invert = false; //this would be always false
			$pattern = new StaticBlock(BlockParser::parseBlockIdentifier($patternString));
		} catch (ParseError) {
			//This still allows old syntax, starting with #
			//I have no idea what phpstorm is doing here
			//TODO: find a better expression without things phpstorm doesn't like
			/** @noinspection all */
			if (!((bool) preg_match("/#?([^()]*)(?:\(((?:(?R))+)\))?/", $patternString, $matches))) {
				throw new ParseError($patternString . " does not follow pattern rules");
			}

			if (($invert = str_starts_with($matches[1], "!"))) {
				$matches[1] = substr($matches[1], 1);
			}

			if ($matches[1] === "") {
				$pattern = self::parseInternal($matches[2]);
			} else {
				$pattern = self::getPattern($matches[1], isset($matches[2]) ? [self::parseInternal($matches[2])] : []);
			}
		}

		$pattern->setWeight((int) $weight);
		return $invert ? new NotPattern($pattern) : $pattern;
	}

	/**
	 * @param string    $pattern
	 * @param Pattern[] $children
	 * @return Pattern
	 */
	private static function getPattern(string $pattern, array $children = []): Pattern
	{
		$args = explode(";", $pattern);
		return match (array_shift($args)) {
			"not" => new NotPattern(new PatternWrapper($children)),
			"even" => new EvenPattern(AxisArgumentWrapper::parse($args), $children),
			"odd" => new OddPattern(AxisArgumentWrapper::parse($args), $children),
			"divisible" => new DivisiblePattern(AxisArgumentWrapper::parse($args), (int) ($args[0] ?? -1), $children),
			"block" => new BlockPattern(self::getBlockType($args[0] ?? ""), $children),
			"above" => new AbovePattern(self::getBlockType($args[0] ?? ""), $children),
			"below" => new BelowPattern(self::getBlockType($args[0] ?? ""), $children),
			"around" => new AroundPattern(self::getBlockType($args[0] ?? ""), $children),
			"horizontal", "horizon" => new HorizontalPattern(self::getBlockType($args[0] ?? ""), $children),
			"nat", "naturalized" => new NaturalizePattern($children[0] ?? null, $children[1] ?? null, $children[2] ?? null),
			"walls", "wall" => new WallPattern((float) ($args[0] ?? 1.0), $children),
			"sides", "side" => new SidesPattern((float) ($args[0] ?? 1.0), $children),
			"center", "middle" => new CenterPattern($children),
			"gravity" => new GravityPattern($children),
			"embed", "embeded" => new EmbedPattern(self::getBlockType($args[0] ?? ""), $children),
			"solid" => new BlockPattern(new SolidBlock(), $children),
			default => throw new ParseError("Unknown Pattern " . $pattern, true)
		};
	}

	/**
	 * @param string $string
	 * @return BlockType
	 */
	public static function getBlockType(string $string): BlockType
	{
		if ($string === "solid") {
			return new SolidBlock();
		}

		if (BlockParser::isStatic($string)) {
			return new StaticBlock(BlockParser::parseBlockIdentifier($string));
		}

		return new DynamicBlock(BlockParser::parseBlockIdentifier($string));
	}
}