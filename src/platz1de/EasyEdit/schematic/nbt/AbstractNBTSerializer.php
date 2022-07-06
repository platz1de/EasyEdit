<?php

namespace platz1de\EasyEdit\schematic\nbt;

use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\NBT;
use pocketmine\nbt\NbtDataException;
use pocketmine\nbt\ReaderTracker;
use pocketmine\nbt\TreeRoot;
use pocketmine\utils\BinaryDataException;

class AbstractNBTSerializer extends BigEndianNbtSerializer
{
	/**
	 * @var CompressedFileStream
	 */
	protected $buffer;

	/**
	 * @throws BinaryDataException
	 * @throws NbtDataException
	 */
	public function readFile(string $file): TreeRoot
	{
		$this->buffer = new CompressedFileStream($file);
		$type = $this->readByte();
		if ($type === NBT::TAG_End) {
			throw new NbtDataException("Found TAG_End at the start of buffer");
		}

		$rootName = $this->readString();
		return new TreeRoot(AbstractNBT::createTag($type, $this, new ReaderTracker(0)), $rootName);
	}

	public function setReadOffset(int $offset): void
	{
		$this->buffer->setOffset($offset);
	}

	public function getReadOffset(): int
	{
		return $this->buffer->getOffset();
	}

	public function readChunk(int $count): string
	{
		return $this->buffer->get($count);
	}

	public function skip(int $count): void
	{
		$this->buffer->setOffset($this->buffer->getOffset() + $count);
	}

	public function close(): void
	{
		$this->buffer->close();
	}

	public function __clone(): void
	{
		$this->buffer = clone $this->buffer;
	}
}