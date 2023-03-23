<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use Generator;
use platz1de\EasyEdit\math\BlockVector;
use platz1de\EasyEdit\selection\constructor\BinaryStreamConstructor;
use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\world\World;

/**
 * Only made for tiny amounts of blocks.
 * Ideal when size is unknown
 */
class BinaryBlockListStream extends BlockListSelection
{
	private ExtendedBinaryStream $blocks;
	/**
	 * @var int[]
	 */
	private array $chunks = [];

	/**
	 * @param string $world
	 */
	public function __construct(string $world)
	{
		parent::__construct($world, BlockVector::zero(), BlockVector::zero());
		$this->blocks = new ExtendedBinaryStream();
	}

	public function addBlock(int $x, int $y, int $z, int $id, bool $overwrite = true): void
	{
		if ($this->chunks === [] || array_key_last($this->chunks) !== World::chunkHash($x >> 4, $z >> 4)) {
			$this->chunks[World::chunkHash($x >> 4, $z >> 4)] = strlen($this->blocks->getBuffer());
		}
		$this->blocks->putInt($x);
		$this->blocks->putInt($y);
		$this->blocks->putInt($z);
		$this->blocks->putInt($id);
	}

	public function getBlockCount(): int
	{
		return (int) (strlen($this->blocks->getBuffer()) / 16); //each blocks consists of 4 integers, which are 4 bytes each
	}

	public function getNeededChunks(): array
	{
		return array_keys($this->chunks);
	}

	/**
	 * @param Closure          $closure
	 * @param SelectionContext $context
	 * @return Generator<ShapeConstructor>
	 */
	public function asShapeConstructors(Closure $closure, SelectionContext $context): Generator
	{
		yield new BinaryStreamConstructor($closure, $this->blocks, $this->chunks);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);
		$stream->putString($this->blocks->getBuffer());
		$stream->putInt(count($this->chunks));
		foreach ($this->chunks as $chunk => $offset) {
			$stream->putLong($chunk);
			$stream->putLong($offset);
		}
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);
		$this->blocks = new ExtendedBinaryStream($stream->getString());
		$chunks = $stream->getInt();
		for ($i = 0; $i < $chunks; $i++) {
			/** @noinspection AmbiguousMethodsCallsInArrayMappingInspection */
			$this->chunks[$stream->getLong()] = $stream->getLong();
		}
	}

	public function free(): void
	{
		parent::free();
		$this->blocks = new ExtendedBinaryStream();
	}

	/**
	 * @return string
	 */
	public function getData(): string
	{
		return $this->blocks->getBuffer();
	}

	/**
	 * @param string $blocks
	 */
	public function setData(string $blocks): void
	{
		$this->blocks = new ExtendedBinaryStream($blocks);
	}

	public function createSafeClone(): BinaryBlockListStream
	{
		$clone = new self($this->getWorldName());
		$clone->setData($this->getData());
		$clone->chunks = $this->chunks;
		foreach ($this->getTiles($this->getPos1(), $this->getPos2()) as $tile) {
			$clone->addTile($tile);
		}
		return $clone;
	}

	public function containsData(): bool
	{
		return $this->blocks->getBuffer() !== "" || parent::containsData();
	}
}