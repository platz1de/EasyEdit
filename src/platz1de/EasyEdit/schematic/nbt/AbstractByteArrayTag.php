<?php

namespace platz1de\EasyEdit\schematic\nbt;

use BadMethodCallException;
use pocketmine\nbt\NBT;
use pocketmine\nbt\NbtDataException;
use pocketmine\nbt\NbtStreamWriter;
use pocketmine\nbt\tag\Tag;

class AbstractByteArrayTag extends Tag
{
	public const CHUNK_SIZE = 65536;
	private int $length;
	private int $current = 0;
	private AbstractNBTSerializer $reader;

	public static function read(AbstractNBTSerializer $reader): self
	{
		$result = new self;
		$result->length = $reader->readInt();
		$result->reader = clone $reader;

		$reader->skip($result->length);

		return $result;
	}

	public function getValue()
	{
		throw new BadMethodCallException("Abstract byte array cannot be read at once");
	}

	public function nextChunk(int $sizeOffset = 0): string
	{
		if ($this->current >= $this->length) {
			throw new NbtDataException("No more bytes left to read");
		}
		$r = $this->reader->readChunk(min(self::CHUNK_SIZE + $sizeOffset, $this->length - $this->current));
		$this->current += self::CHUNK_SIZE + $sizeOffset;
		return $r;
	}

	public function getType(): int
	{
		return NBT::TAG_ByteArray;
	}

	public function write(NbtStreamWriter $writer): void
	{
		throw new \RuntimeException("unimplemented");
	}

	protected function getTypeName(): string
	{
		return "ByteArray";
	}

	protected function stringifyValue(int $indentation): string
	{
		return "abstract";
	}

	protected function makeCopy(): Tag
	{
		return clone $this;
	}

	public function close(): void
	{
		$this->reader->close();
	}
}