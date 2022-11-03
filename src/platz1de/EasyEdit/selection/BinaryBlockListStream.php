<?php

namespace platz1de\EasyEdit\selection;

use BadMethodCallException;
use Closure;
use Generator;
use platz1de\EasyEdit\selection\constructor\BinaryStreamConstructor;
use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\math\Vector3;
use pocketmine\world\World;

/**
 * Only made for tiny amounts of blocks.
 * Ideal when size is unknown
 */
class BinaryBlockListStream extends BlockListSelection
{
	private ExtendedBinaryStream $blocks;

	/**
	 * @param string $world
	 */
	public function __construct(string $world)
	{
		parent::__construct($world, Vector3::zero(), Vector3::zero());
		$this->blocks = new ExtendedBinaryStream();
	}

	/**
	 * @param string $world
	 */
	public function setWorld(string $world): void
	{
		$this->world = $world;
	}

	public function addBlock(int $x, int $y, int $z, int $id, bool $overwrite = true): void
	{
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
		$this->blocks->rewind();
		$x = $this->blocks->getInt() >> 4;
		$this->blocks->getInt(); //y
		$z = $this->blocks->getInt() >> 4;
		return [World::chunkHash($x, $z)];
	}

	public function shouldBeCached(int $x, int $z): bool
	{
		return false;
	}

	/**
	 * @param Closure          $closure
	 * @param SelectionContext $context
	 * @return Generator<ShapeConstructor>
	 */
	public function asShapeConstructors(Closure $closure, SelectionContext $context): Generator
	{
		yield new BinaryStreamConstructor($closure, $this->blocks);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);
		$stream->putString($this->blocks->getBuffer());
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);
		$this->blocks = new ExtendedBinaryStream($stream->getString());
	}

	public function free(): void
	{
		parent::free();
		$this->blocks = new ExtendedBinaryStream();
	}

	public function merge(BlockListSelection $selection): void
	{
		if (!$selection instanceof self) {
			throw new BadMethodCallException("Can't merge block lists of different types");
		}

		parent::merge($selection);

		$this->blocks->put($selection->getData());
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