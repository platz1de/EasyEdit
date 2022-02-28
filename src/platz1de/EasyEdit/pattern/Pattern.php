<?php

namespace platz1de\EasyEdit\pattern;

use Exception;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\world\SafeSubChunkExplorer;
use pocketmine\utils\AssumptionFailedError;

class Pattern
{
	/**
	 * @var Pattern[]
	 */
	protected array $pieces;
	protected PatternArgumentData $args;

	/**
	 * @param Pattern[]                $pieces
	 * @param PatternArgumentData|null $args
	 * @return Pattern
	 */
	public static function from(array $pieces, ?PatternArgumentData $args = null): Pattern
	{
		if ((static::class === __CLASS__) && count($pieces) === 1 && $pieces[0]->getWeight() === 100) {
			return $pieces[0]; //no need to wrap single patterns
		}

		if (count($pieces) === 1 && $pieces[0]::class === __CLASS__) {
			$pieces = $pieces[0]->pieces; //no double-wrapping
		}

		$name = static::class;
		return new $name($pieces, $args);
	}

	/**
	 * Pattern constructor.
	 * @param Pattern[]                $pieces
	 * @param PatternArgumentData|null $args
	 */
	final private function __construct(array $pieces, ?PatternArgumentData $args = null)
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
	 * @param int                  $y may be changed by patterns
	 * @param int                  $z
	 * @param SafeSubChunkExplorer $iterator
	 * @param Selection            $current
	 * @param Selection            $total
	 * @return int
	 */
	public function getFor(int $x, int &$y, int $z, SafeSubChunkExplorer $iterator, Selection $current, Selection $total): int
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
	 * @param int                  $x
	 * @param int                  $y
	 * @param int                  $z
	 * @param SafeSubChunkExplorer $iterator
	 * @param Selection            $current
	 * @param Selection            $total
	 * @return bool
	 */
	public function isValidAt(int $x, int $y, int $z, SafeSubChunkExplorer $iterator, Selection $current, Selection $total): bool
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
		return $this->args->getWeight();
	}

	/**
	 * @param int $weight
	 */
	public function setWeight(int $weight): void
	{
		$this->args->setWeight($weight);
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
		/** @phpstan-var class-string<Pattern> $type */
		$type = $stream->getString();
		return new $type($pieces, $args);
	}
}