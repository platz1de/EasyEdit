<?php

namespace platz1de\EasyEdit\pattern\parser;

use platz1de\EasyEdit\pattern\block\DynamicBlock;
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
use platz1de\EasyEdit\pattern\PatternArgumentData;
use platz1de\EasyEdit\pattern\PatternConstruct;
use platz1de\EasyEdit\utils\BlockParser;
use pocketmine\player\Player;

class PatternParser
{
	/**
	 * Parses player input (mostly commands)
	 * @param string $pattern
	 * @param Player $player
	 * @return Pattern
	 */
	public static function parseInput(string $pattern, Player $player): Pattern
	{
		return self::parseInternal(str_replace("hand", $player->getInventory()->getItemInHand()->getBlock()->getName(), $pattern));
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
		//basically magic
		preg_match_all("/(?:\((?:[^()]+|(?R))+\)|[^(),\s]+)+/", $pattern, $matches);
		$pieces = [];
		foreach ($matches[0] as $piece) {
			$pieces[] = self::parseLogical($piece);
		}
		return Pattern::from($pieces);
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
		return PatternConstruct::from($pieces);
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

		//blocks have priority
		try {
			$invert = false; //this would be always false
			$pattern = StaticBlock::fromBlock(BlockParser::getBlock($patternString));
		} catch (ParseError) {
			//This still allows old syntax, starting with #
			//I have no idea what phpstorm is doing here
			//TODO: find a better expression without things phpstorm doesn't like
			/** @noinspection all */
			if (!((bool) preg_match("/#?([^()]*)(?:\(((?R)+)\))?/", $patternString, $matches))) {
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

		$pattern->setWeight($weight);
		return $invert ? NotPattern::from([$pattern]) : $pattern;
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
			"not" => NotPattern::from($children),
			"even" => EvenPattern::from($children, PatternArgumentData::create()->parseAxes($args)),
			"odd" => OddPattern::from($children, PatternArgumentData::create()->parseAxes($args)),
			"divisible" => DivisiblePattern::from($children, PatternArgumentData::create()->parseAxes($args)->setInt("count", (int) ($args[0] ?? -1))),
			"block" => BlockPattern::from($children, PatternArgumentData::fromBlockType($args[0] ?? "")),
			"above" => AbovePattern::from($children, PatternArgumentData::fromBlockType($args[0] ?? "")),
			"below" => BelowPattern::from($children, PatternArgumentData::fromBlockType($args[0] ?? "")),
			"around" => AroundPattern::from($children, PatternArgumentData::fromBlockType($args[0] ?? "")),
			"horizontal", "horizon" => HorizontalPattern::from($children, PatternArgumentData::fromBlockType($args[0] ?? "")),
			"nat", "naturalized" => NaturalizePattern::from($children),
			"walls", "wall" => WallPattern::from($children, PatternArgumentData::create()->setFloat("thickness", (float) ($args[0] ?? 1.0))),
			"sides", "side" => SidesPattern::from($children, PatternArgumentData::create()->setFloat("thickness", (float) ($args[0] ?? 1.0))),
			"center", "middle" => CenterPattern::from($children),
			"gravity" => GravityPattern::from($children),
			"embed", "embeded" => EmbedPattern::from($children, PatternArgumentData::fromBlockType($args[0] ?? "")),
			default => throw new ParseError("Unknown Pattern " . $pattern, true)
		};
	}

	/**
	 * @param string      $string
	 * @param Player|null $player
	 * @return StaticBlock
	 */
	public static function getBlockType(string $string, ?Player $player = null): StaticBlock
	{
		if ($player instanceof Player && $string === "hand") {
			return StaticBlock::fromBlock($player->getInventory()->getItemInHand()->getBlock());
		}

		if (BlockParser::isStatic($string)) {
			return StaticBlock::fromBlock(BlockParser::getBlock($string));
		}

		return DynamicBlock::fromBlock(BlockParser::getBlock($string));
	}
}