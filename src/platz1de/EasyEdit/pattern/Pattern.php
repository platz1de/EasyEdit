<?php

namespace platz1de\EasyEdit\pattern;

use Exception;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\level\utils\SubChunkIteratorManager;

class Pattern
{
	/**
	 * @var Pattern[]
	 */
	protected $pieces;
	/**
	 * @var array
	 */
	protected $args;

	public function __construct(array $pieces, array $args)
	{
		$this->pieces = $pieces;
		$this->args = $args;
	}

	public function check(): void
	{
	}

	/**
	 * @param int                     $x
	 * @param int                     $y
	 * @param int                     $z
	 * @param SubChunkIteratorManager $iterator
	 * @return Block|null
	 */
	public function getFor(int $x, int $y, int $z, SubChunkIteratorManager $iterator): ?Block
	{
		foreach ($this->pieces as $piece) {
			if ($piece->isValidAt($x, $y, $z, $iterator)) {
				return $piece->getFor($x, $y, $z, $iterator);
			}
		}
		return null;
	}

	/**
	 * @param int                     $x
	 * @param int                     $y
	 * @param int                     $z
	 * @param SubChunkIteratorManager $iterator
	 * @return bool
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function isValidAt(int $x, int $y, int $z, SubChunkIteratorManager $iterator): bool
	{
		return false;
	}

	/**
	 * @param string $pattern
	 * @return Pattern
	 * @throws ParseError
	 */
	public static function parse(string $pattern): Pattern
	{
		return new Pattern(self::processPattern(self::parsePiece($pattern)), []);
	}

	/**
	 * @param string $pattern
	 * @param int    $start
	 * @return array
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
						throw new ParseError("Unexpected delimiter in Pattern", $i + $start);
					}

					if (self::isBlock($current)) {
						$pieces[] = self::getBlock($current);
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
			if (self::isBlock($current)) {
				$pieces[] = self::getBlock($current);
			} else {
				throw new ParseError("Invalid Block " . $current);
			}
		} elseif ($piece !== "") {
			throw new ParseError("Pattern was never Closed");
		}

		return $pieces;
	}

	/**
	 * @param array $pattern
	 * @return Pattern[]|Block[]
	 */
	private static function processPattern(array $pattern): array
	{
		$pieces = [];
		foreach ($pattern as $name => $p) {
			if ($p instanceof Block) {
				$pieces[] = new BlockPattern($p);
			} else {
				$pa = self::getPattern($name, self::processPattern($p));
				$pa->check();
				$pieces[] = $pa;
			}
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
		}
	}

	/**
	 * @param string $pattern
	 * @param array  $children
	 * @return Pattern
	 */
	private static function getPattern(string $pattern, array $children = []): Pattern
	{
		if ($pattern[0] === "!") {
			return new Not(self::getPattern(substr($pattern, 1), $children));
		}

		$args = explode(";", $pattern);
		switch (array_shift($args)) {
			case "even":
				return new Even($children, $args);
			case "odd":
				return new Odd($children, $args);
			case "divisible":
				return new Divisible($children, $args);
			case "above":
				return new Above($children, $args);
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

		if (!$block instanceof Block) {
			throw new ParseError("Unknown Block " . $string);
		}

		return $block;
	}
}