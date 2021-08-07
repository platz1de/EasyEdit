<?php

namespace platz1de\EasyEdit\pattern;

use Exception;
use platz1de\EasyEdit\pattern\block\DynamicBlock;
use platz1de\EasyEdit\pattern\block\StaticBlock;
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
use platz1de\EasyEdit\pattern\random\RandomPattern;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\SafeSubChunkExplorer;
use pocketmine\block\Block;
use pocketmine\item\LegacyStringToItemParser;

class Pattern
{
	private const INTERNAL_BLOCK = "staticBlockInternal";

	/**
	 * @var Pattern[]
	 */
	protected $pieces;
	/**
	 * @var PatternArgumentData
	 */
	protected $args;

	/**
	 * Pattern constructor.
	 * @param Pattern[]                $pieces
	 * @param PatternArgumentData|null $args
	 */
	final public function __construct(array $pieces, ?PatternArgumentData $args = null)
	{
		$this->pieces = $pieces;
		$this->args = $args ?? new PatternArgumentData();
		$this->check();
	}

	public function check(): void
	{
	}

	/**
	 * @param int                  $x
	 * @param int                  $y
	 * @param int                  $z
	 * @param SafeSubChunkExplorer $iterator
	 * @param Selection            $selection
	 * @return Block|null
	 */
	public function getFor(int $x, int $y, int $z, SafeSubChunkExplorer $iterator, Selection $selection): ?Block
	{
		foreach ($this->pieces as $piece) {
			if ($piece->isValidAt($x, $y, $z, $iterator, $selection)) {
				return $piece->getFor($x, $y, $z, $iterator, $selection);
			}
		}
		return null;
	}

	/**
	 * @param int                  $x
	 * @param int                  $y
	 * @param int                  $z
	 * @param SafeSubChunkExplorer $iterator
	 * @param Selection            $selection
	 * @return bool
	 */
	public function isValidAt(int $x, int $y, int $z, SafeSubChunkExplorer $iterator, Selection $selection): bool
	{
		return true;
	}

	/**
	 * @return string
	 */
	public function fastSerialize(): string
	{
		$stream = new ExtendedBinaryStream();
		$stream->putString($this->args->fastSerialize());
		$stream->putInt(count($this->pieces));
		foreach ($this->pieces as $piece) {
			$stream->putString($piece->fastSerialize());
		}
		$stream->putString(static::class);
		return $stream->getBuffer();
	}

	/**
	 * @param string $data
	 * @return Pattern
	 */
	public static function fastDeserialize(string $data): Pattern
	{
		$stream = new ExtendedBinaryStream($data);
		$args = PatternArgumentData::fastDeserialize($stream->getString());
		$pieces = [];
		for ($i = $stream->getInt(); $i > 0; $i--) {
			$pieces[] = self::fastDeserialize($stream->getString());
		}
		$type = $stream->getString();
		return new $type($pieces, $args);
	}

	/**
	 * @param string $pattern
	 * @return Pattern
	 * @throws ParseError
	 */
	public static function parse(string $pattern): Pattern
	{
		try {
			return new Pattern(self::processPattern(self::parsePiece($pattern)));
		} catch (Exception $exception) {
			throw new ParseError($exception->getMessage(), null, false); //the difference is purely internally
		}
	}

	/**
	 * @param string $pattern
	 * @param int    $start
	 * @return array<string, array>
	 * @throws ParseError
	 */
	public static function parsePiece(string $pattern, int $start = 1): array
	{
		if ($pattern === "") {
			throw new ParseError("No pattern given");
		}

		$parse = str_split(strtolower($pattern));
		$pieces = [];
		$current = "";
		$isPattern = false;
		$piece = "";
		$needEnd = false;

		foreach ($parse as $i => $str) {
			if ($str === ")") {
				/** @noinspection NotOptimalIfConditionsInspection */
				if ($piece === "" && $isPattern) {
					throw new ParseError("Pattern was never opened", $i + $start);
				}

				if (substr_count($current, "(") !== substr_count($current, ")")) {
					$current .= $str;
					continue;
				}
				$pieces[$piece] = self::parsePiece($current, $i + $start + 2);
				$current = "";
				$isPattern = false;
				$piece = "";
				$needEnd = true;
			} elseif ($needEnd) {
				if ($str === ",") {
					$needEnd = false;
				} else {
					throw new ParseError("Missing delimiter", $i + $start);
				}
			} elseif ($piece === "") {
				if ($str === ",") {
					if ($isPattern) {
						if (self::isPattern($current)) {
							$pieces[$current] = [];
							$current = "";
							$isPattern = false;
						} else {
							throw new ParseError("Unknown Pattern " . $current, $i + $start);
						}
					} elseif (self::isBlock($current)) {
						$pieces[self::INTERNAL_BLOCK . ";" . $current] = [];
						$current = "";
					} else {
						throw new ParseError("Invalid Block " . $current);
					}
				} elseif ($str === "#") {
					if ($current === "" && $isPattern === false) {
						$isPattern = true;
					} else {
						throw new ParseError("Invalid Pattern operator", $i + $start);
					}
				} elseif ($str === "(") {
					if ($isPattern) {
						if (self::isPattern($current)) {
							$piece = $current;
							$current = "";
						} else {
							throw new ParseError("Unknown Pattern " . $current, $i + $start);
						}
					} else {
						throw new ParseError("Cannot use nested Patterns in Block context", $i + $start);
					}
				} else {
					$current .= $str;
				}
			} else {
				$current .= $str;
			}
		}

		if (!$needEnd && $current !== "") {
			if ($isPattern) {
				if (self::isPattern($current)) {
					$pieces[$current] = [];
				} else {
					throw new ParseError("Unknown Pattern " . $current, count($parse) + $start);
				}
			} elseif (self::isBlock($current)) {
				$pieces[self::INTERNAL_BLOCK . ";" . $current] = [];
			} else {
				throw new ParseError("Invalid Block " . $current, count($parse) + $start);
			}
		} elseif ($piece !== "") {
			throw new ParseError("Pattern was never Closed", count($parse) + $start);
		}

		return $pieces;
	}

	/**
	 * @param array<string, array> $pattern
	 * @return Pattern[]
	 */
	public static function processPattern(array $pattern): array
	{
		$pieces = [];
		foreach ($pattern as $name => $p) {
			$pa = self::getPattern($name, self::processPattern($p));
			$pa->check();
			$pieces[] = $pa;
		}
		return $pieces;
	}

	/**
	 * @param string $pattern
	 * @return bool
	 */
	private static function isPattern(string $pattern): bool
	{
		try {
			self::getPattern($pattern);
			return true;
		} catch (ParseError $exception) {
			return $exception instanceof WrongPatternUsageException;
		}
	}

	/**
	 * @param string    $pattern
	 * @param Pattern[] $children
	 * @return Pattern
	 */
	private static function getPattern(string $pattern, array $children = []): Pattern
	{
		if ($pattern[0] === "!") {
			$pa = self::getPattern(substr($pattern, 1), $children);
			$pa->check();
			return new NotPattern([$pa]);
		}

		$args = explode(";", $pattern);
		switch (array_shift($args)) {
			case self::INTERNAL_BLOCK:
				return StaticBlock::from(self::getBlock($args[0]));
			case "not":
				return new NotPattern($children);
			case "even":
				return new EvenPattern($children, PatternArgumentData::create()->parseAxes($args));
			case "odd":
				return new OddPattern($children, PatternArgumentData::create()->parseAxes($args));
			case "divisible":
				return new DivisiblePattern($children, PatternArgumentData::create()->parseAxes($args)->setInt("count", (int) $args[0]));
			case "block":
				return new BlockPattern($children, PatternArgumentData::create()->setBlock(self::getBlockType($args[0])));
			case "above":
				return new AbovePattern($children, PatternArgumentData::create()->setBlock(self::getBlockType($args[0])));
			case "below":
				return new BelowPattern($children, PatternArgumentData::create()->setBlock(self::getBlockType($args[0])));
			case "around":
				return new AroundPattern($children, PatternArgumentData::create()->setBlock(self::getBlockType($args[0])));
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
				return new WallPattern($children);
			case "sides":
			case "side":
				return new SidesPattern($children);
			case "center":
			case "middle":
				return new CenterPattern($children);
		}

		throw new ParseError("Unknown Pattern " . $pattern);
	}

	/**
	 * @param string $block
	 * @return bool
	 */
	public static function isBlock(string $block): bool
	{
		try {
			self::getBlock($block);
			return true;
		} catch (ParseError $exception) {
			return false;
		}
	}

	/**
	 * @param string $string
	 * @return Block
	 * @throws ParseError
	 */
	public static function getBlock(string $string): Block
	{
		try {
			$item = LegacyStringToItemParser::getInstance()->parse($string);
		} catch (Exception $exception) {
			throw new ParseError("Unknown Block " . $string);
		}

		try {
			$block = $item->getBlock();
		} catch (Exception $exception) {
			throw new ParseError("Unknown Block " . $string);
		}

		return $block;
	}

	/**
	 * @param string $string
	 * @return StaticBlock
	 */
	public static function getBlockType(string $string): StaticBlock
	{
		if (isset(explode(":", str_replace([" ", "minecraft:"], ["_", ""], trim($string)))[1])) {
			return StaticBlock::from(self::getBlock($string));
		}

		return DynamicBlock::from(self::getBlock($string));
	}
}