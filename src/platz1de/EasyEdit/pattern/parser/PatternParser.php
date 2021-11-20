<?php

namespace platz1de\EasyEdit\pattern\parser;

use platz1de\EasyEdit\pattern\block\DynamicBlock;
use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\pattern\parser\reader\BlockReader;
use platz1de\EasyEdit\pattern\parser\reader\PatternConstructReader;
use platz1de\EasyEdit\pattern\parser\reader\PieceReader;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\utils\BlockParser;
use pocketmine\player\Player;
use Throwable;

class PatternParser
{
	/**
	 * @var class-string<PieceReader>[]
	 */
	private static array $parsers = [
		BlockReader::class,
		PatternConstructReader::class
	];

	/**
	 * Parses player input (mostly commands)
	 * @param string $pattern
	 * @param Player $player
	 * @return Pattern
	 */
	public static function parseInput(string $pattern, Player $player): Pattern
	{
		return new Pattern(self::parseInputArgument($pattern, $player));
	}

	/**
	 * Parses player input as argument for other patterns, needed for helper commands like replace
	 * @param string $pattern
	 * @param Player $player
	 * @return Pattern[]
	 */
	public static function parseInputArgument(string $pattern, Player $player): array
	{
		return self::parseInternal(str_replace("hand", $player->getInventory()->getItemInHand()->getBlock()->getName(), $pattern));
	}

	/**
	 * Parses internal saved patterns
	 * @param string $pattern
	 * @return Pattern[]
	 * @throws ParseError
	 */
	public static function parseInternal(string $pattern): array
	{
		try {
			//basically magic
			preg_match_all("/\((?:[^()]|(?R))+\)|[^(),\s]+(?R)|[^(),\s]+/", $pattern, $matches);
			$pieces = [];
			foreach ($matches[0] as $piece) {
				$pieces[] = self::parsePiece($piece);
			}
			return $pieces;
		} catch (Throwable $exception) {
			throw new ParseError($exception->getMessage(), null, false); //the difference is purely internally
		}
	}

	/**
	 * @param string $input
	 * @param int    $offset
	 * @return Pattern
	 */
	public static function parsePiece(string $input, int $offset = 1): Pattern
	{
		if ($input === "") {
			throw new ParseError("No pattern given");
		}

		foreach (self::$parsers as $parser) {
			try {
				return $parser::readPiece($input);
			} catch (ParseError $exception) {
				if ($exception->isPriority()) {
					throw $exception->offset($offset);
				}
			}
		}

		throw new ParseError("Failed to parse piece " . $input);
	}

	/**
	 * @param string      $string
	 * @param Player|null $player
	 * @return StaticBlock
	 */
	public static function getBlockType(string $string, ?Player $player = null): StaticBlock
	{
		if ($player instanceof Player && $string === "hand") {
			return StaticBlock::from($player->getInventory()->getItemInHand()->getBlock());
		}

		if (BlockParser::isStatic($string)) {
			return StaticBlock::from(BlockParser::getBlock($string));
		}

		return DynamicBlock::from(BlockParser::getBlock($string));
	}
}