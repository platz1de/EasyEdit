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
use platz1de\EasyEdit\utils\SafeSubChunkIteratorManager;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use UnexpectedValueException;

class Pattern
{
	/**
	 * @var Pattern[]
	 */
	protected $pieces;
	/**
	 * @var array<int, mixed>
	 */
	protected $args;

	/**
	 * Pattern constructor.
	 * @param Pattern[]         $pieces
	 * @param array<int, mixed> $args
	 */
	public function __construct(array $pieces, array $args)
	{
		$this->pieces = $pieces;
		$this->args = $args;
	}

	public function check(): void
	{
	}

	/**
	 * @param int                         $x
	 * @param int                         $y
	 * @param int                         $z
	 * @param SafeSubChunkIteratorManager $iterator
	 * @param Selection                   $selection
	 * @return Block|null
	 */
	public function getFor(int $x, int $y, int $z, SafeSubChunkIteratorManager $iterator, Selection $selection): ?Block
	{
		foreach ($this->pieces as $piece) {
			if ($piece->isValidAt($x, $y, $z, $iterator, $selection)) {
				return $piece->getFor($x, $y, $z, $iterator, $selection);
			}
		}
		return null;
	}

	/**
	 * @param int                         $x
	 * @param int                         $y
	 * @param int                         $z
	 * @param SafeSubChunkIteratorManager $iterator
	 * @param Selection                   $selection
	 * @return bool
	 */
	public function isValidAt(int $x, int $y, int $z, SafeSubChunkIteratorManager $iterator, Selection $selection): bool
	{
		return true;
	}

	/**
	 * @param string $pattern
	 * @return Pattern
	 * @throws ParseError
	 */
	public static function parse(string $pattern): Pattern
	{
		try {
			return new Pattern(self::processPattern(self::parsePiece($pattern)), []);
		} catch (UnexpectedValueException $exception) {
			throw new ParseError($exception->getMessage()); //the difference is purely internally
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
						$pieces["staticBlockInternal;" . $current] = [];
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
					throw new ParseError("Unknown Pattern " . $current);
				}
			} elseif (self::isBlock($current)) {
				$pieces["staticBlockInternal;" . $current] = [];
			} else {
				throw new ParseError("Invalid Block " . $current);
			}
		} elseif ($piece !== "") {
			throw new ParseError("Pattern was never Closed");
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
			return false;
		} catch (UnexpectedValueException $exception) {
			return true; //This Pattern exists, but is not complete
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
			return new NotPattern($pa);
		}

		$args = explode(";", $pattern);
		switch (array_shift($args)) {
			case "staticBlockInternal":
				return new StaticBlock(self::getBlock($args[0]));
			case "not":
				return new NotPattern($children[0] ?? null);
			case "even":
				return new EvenPattern($children, $args);
			case "odd":
				return new OddPattern($children, $args);
			case "divisible":
				return new DivisiblePattern($children, $args);
			case "block":
				return new BlockPattern($children, $args);
			case "above":
				return new AbovePattern($children, $args);
			case "below":
				return new BelowPattern($children, $args);
			case "around":
				return new AroundPattern($children, $args);
			case "rand":
			case "random":
				return new RandomPattern($children, $args);
			case "nat":
			case "naturalized":
				return new NaturalizePattern($children, $args);
			case "smooth":
				return new SmoothPattern($children, $args);
			case "walls":
			case "wall":
				return new WallPattern($children, $args);
			case "sides":
			case "side":
				return new SidesPattern($children, $args);
			case "center":
			case "middle":
				return new CenterPattern($children, $args);
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
			$item = ItemFactory::fromString($string);
		} catch (Exception $exception) {
			throw new ParseError("Unknown Block " . $string);
		}

		if (!$item instanceof Item) {
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
			return new StaticBlock(self::getBlock($string));
		}

		return new DynamicBlock(self::getBlock($string));
	}
}