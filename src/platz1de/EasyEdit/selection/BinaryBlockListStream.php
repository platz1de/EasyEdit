<?php

namespace platz1de\EasyEdit\selection;

use BadMethodCallException;
use Closure;
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
	 * @param string $player
	 * @param string $world
	 * @param bool   $piece
	 */
	public function __construct(string $player, string $world, bool $piece = false)
	{
		parent::__construct($player, $world, Vector3::zero(), Vector3::zero(), $piece);
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

	public function isChunkOfSelection(int $x, int $z): bool
	{
		return true;
	}

	public function shouldBeCached(int $x, int $z): bool
	{
		return false;
	}

	public function useOnBlocks(Closure $closure, SelectionContext $context, Selection $full): void
	{
		$this->blocks->rewind();
		while (!$this->blocks->feof()) {
			$closure($this->blocks->getInt(), $this->blocks->getInt(), $this->blocks->getInt(), $this->blocks->getInt());
		}
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

	//TODO: offset
	public function split(Vector3 $offset): array
	{
		$this->blocks->rewind();

		$pieces = [];
		$current = null;
		$currentChunkX = 0;
		$currentChunkZ = 0;
		while (!$this->blocks->feof()) {
			$x = $this->blocks->getInt();
			$y = $this->blocks->getInt();
			$z = $this->blocks->getInt();
			$id = $this->blocks->getInt();

			if ($current === null || $currentChunkX !== $x >> 4 || $currentChunkZ !== $z >> 4) {
				$currentChunkX = $x >> 4;
				$currentChunkZ = $z >> 4;
				$pieces[] = $current = new self($this->getPlayer(), $this->getWorldName(), true);
			}

			$current->addBlock($x, $y, $z, $id);
		}
		return $pieces;
	}

	public function createSafeClone(): BinaryBlockListStream
	{
		$clone = new self($this->getPlayer(), $this->getWorldName());
		$clone->setData($this->getData());
		foreach ($this->getTiles() as $tile) {
			$this->addTile($tile);
		}
		return $clone;
	}
}