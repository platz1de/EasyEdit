<?php

namespace platz1de\EasyEdit\pattern\parser\reader;

use platz1de\EasyEdit\pattern\functional\NaturalizePattern;
use platz1de\EasyEdit\pattern\functional\SmoothPattern;
use platz1de\EasyEdit\pattern\logic\math\DivisiblePattern;
use platz1de\EasyEdit\pattern\logic\math\EvenPattern;
use platz1de\EasyEdit\pattern\logic\math\OddPattern;
use platz1de\EasyEdit\pattern\logic\NotPattern;
use platz1de\EasyEdit\pattern\logic\relation\AbovePattern;
use platz1de\EasyEdit\pattern\logic\relation\AroundPattern;
use platz1de\EasyEdit\pattern\logic\relation\BelowPattern;
use platz1de\EasyEdit\pattern\logic\relation\BlockPattern;
use platz1de\EasyEdit\pattern\logic\selection\CenterPattern;
use platz1de\EasyEdit\pattern\logic\selection\SidesPattern;
use platz1de\EasyEdit\pattern\logic\selection\WallPattern;
use platz1de\EasyEdit\pattern\parser\ParseError;
use platz1de\EasyEdit\pattern\parser\PatternParser;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\pattern\PatternArgumentData;
use platz1de\EasyEdit\pattern\random\RandomPattern;

class PatternConstructReader extends PieceReader
{
	/**
	 * @param string $piece
	 * @return Pattern
	 */
	public static function readPiece(string $piece): Pattern
	{
		//This still allows old syntax, starting with #
		if (!((bool) preg_match("/#?([^(]+)(?:\((.*)\)|$)/", $piece, $matches))) {
			throw new ParseError($piece . " does not follow pattern rules");
		}

		if (($invert = str_starts_with($matches[1], "!"))) {
			$matches[1] = substr($matches[1], 1);
		}
		$pattern = self::getPattern($matches[1], isset($matches[2]) ? PatternParser::parseInternal($matches[2]) : []);
		return $invert ? new NotPattern([$pattern]) : $pattern;
	}

	/**
	 * @param string    $pattern
	 * @param Pattern[] $children
	 * @return Pattern
	 */
	private static function getPattern(string $pattern, array $children = []): Pattern
	{
		$args = explode(";", $pattern);
		switch (array_shift($args)) {
			case "not":
				return new NotPattern($children);
			case "even":
				return new EvenPattern($children, PatternArgumentData::create()->parseAxes($args));
			case "odd":
				return new OddPattern($children, PatternArgumentData::create()->parseAxes($args));
			case "divisible":
				return new DivisiblePattern($children, PatternArgumentData::create()->parseAxes($args)->setInt("count", (int) ($args[0] ?? -1)));
			case "block":
				return new BlockPattern($children, PatternArgumentData::fromBlockType($args[0] ?? ""));
			case "above":
				return new AbovePattern($children, PatternArgumentData::fromBlockType($args[0] ?? ""));
			case "below":
				return new BelowPattern($children, PatternArgumentData::fromBlockType($args[0] ?? ""));
			case "around":
				return new AroundPattern($children, PatternArgumentData::fromBlockType($args[0] ?? ""));
			case "rand":
			case "random":
				return new RandomPattern($children);
			case "nat":
			case "naturalized":
				return new NaturalizePattern($children);
			case "smooth":
				return new SmoothPattern([]);
			case "walls":
			case "wall":
				return new WallPattern($children, PatternArgumentData::create()->setFloat("thickness", (float) ($args[0] ?? 1.0)));
			case "sides":
			case "side":
				return new SidesPattern($children, PatternArgumentData::create()->setFloat("thickness", (float) ($args[0] ?? 1.0)));
			case "center":
			case "middle":
				return new CenterPattern($children);
		}

		throw new ParseError("Unknown Pattern " . $pattern, null, true, true);
	}
}