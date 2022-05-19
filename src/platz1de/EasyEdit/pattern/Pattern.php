<?php

namespace platz1de\EasyEdit\pattern;

use Exception;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\world\ChunkController;
use pocketmine\utils\AssumptionFailedError;

abstract class Pattern
{
	/**
	 * @var Pattern[]
	 */
	protected array $pieces;
	private int $weight = 100;

	/**
	 * Pattern constructor.
	 * @param Pattern[] $pieces
	 */
	public function __construct(array $pieces)
	{
		if (count($pieces) === 1 && $pieces[0] instanceof PatternWrapper && $pieces[0]->getWeight() === 100) {
			$pieces = $pieces[0]->pieces;
		}
		$this->pieces = $pieces;
	}

	/**
	 * @param int             $x
	 * @param int             $y may be changed by patterns
	 * @param int             $z
	 * @param ChunkController $iterator
	 * @param Selection       $current
	 * @param Selection       $total
	 * @return int
	 */
	public function getFor(int $x, int &$y, int $z, ChunkController $iterator, Selection $current, Selection $total): int
	{
		try {
			if (count($this->pieces) === 1) {
				if ($this->pieces[0]->isValidAt($x, $y, $z, $iterator, $current, $total) && ($this->pieces[0]->getWeight() === 100 || random_int(1, 100) <= $this->pieces[0]->getWeight())) {
					return $this->pieces[0]->getFor($x, $y, $z, $iterator, $current, $total);
				}
				return -1;
			}
			$sum = array_sum(array_map(static function (Pattern $pattern): int {
				return $pattern->getWeight();
			}, $this->pieces));
			$rand = random_int(0, max($sum, 100));
		} catch (Exception) {
			throw new AssumptionFailedError("Failed to generate random integer");
		}

		foreach ($this->pieces as $piece) {
			$rand -= $piece->getWeight();
			if ($rand <= 0) {
				if ($piece->isValidAt($x, $y, $z, $iterator, $current, $total)) {
					return $piece->getFor($x, $y, $z, $iterator, $current, $total);
				}
				break; //only select one
			}
		}
		return -1;
	}

	/**
	 * @param int             $x
	 * @param int             $y
	 * @param int             $z
	 * @param ChunkController $iterator
	 * @param Selection       $current
	 * @param Selection       $total
	 * @return bool
	 */
	public function isValidAt(int $x, int $y, int $z, ChunkController $iterator, Selection $current, Selection $total): bool
	{
		return true;
	}

	/**
	 * @param class-string<Pattern> $pattern
	 * @return bool
	 */
	public function contains(string $pattern): bool
	{
		if (static::class === $pattern) {
			return true;
		}
		foreach ($this->pieces as $piece) {
			if ($piece->contains($pattern)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @return int
	 */
	public function getWeight(): int
	{
		return $this->weight;
	}

	/**
	 * @param int $weight
	 */
	public function setWeight(int $weight): void
	{
		$this->weight = $weight;
	}

	/**
	 * @return SelectionContext
	 */
	public function getSelectionContext(): SelectionContext
	{
		if (static::class === __CLASS__ && $this->pieces === []) {
			return SelectionContext::full(); //TODO: Add separate Mask pattern types
		}
		$context = SelectionContext::empty();
		$this->applySelectionContext($context);
		return $context;
	}

	/**
	 * @param SelectionContext $context
	 */
	public function applySelectionContext(SelectionContext $context): void
	{
		foreach ($this->pieces as $piece) {
			$piece->applySelectionContext($context);
		}
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	abstract public function putData(ExtendedBinaryStream $stream): void;

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	abstract public function parseData(ExtendedBinaryStream $stream): void;

	/**
	 * @return string
	 */
	public function fastSerialize(): string
	{
		$stream = new ExtendedBinaryStream();
		$stream->putString(igbinary_serialize($this) ?? "");
		$this->putData($stream);
		$stream->putInt(count($this->pieces));
		foreach ($this->pieces as $piece) {
			$stream->putString($piece->fastSerialize());
		}
		return $stream->getBuffer();
	}

	/**
	 * @param string $data
	 * @return Pattern
	 */
	public static function fastDeserialize(string $data): Pattern
	{
		$stream = new ExtendedBinaryStream($data);
		/** @var Pattern $pattern */
		$pattern = igbinary_unserialize($stream->getString());
		$pattern->parseData($stream);
		$pieces = [];
		for ($i = $stream->getInt(); $i > 0; $i--) {
			$pieces[] = self::fastDeserialize($stream->getString());
		}
		$pattern->pieces = $pieces;
		return $pattern;
	}

	/**
	 * @return array{int}
	 */
	public function __serialize(): array
	{
		return [$this->weight];
	}

	/**
	 * @param array{int} $data
	 */
	public function __unserialize(array $data): void
	{
		$this->weight = $data[0];
	}
}