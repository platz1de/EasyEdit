<?php

namespace platz1de\EasyEdit\schematic\nbt;

use BadMethodCallException;
use pocketmine\nbt\NBT;
use pocketmine\nbt\NbtDataException;
use pocketmine\nbt\NbtStreamWriter;
use pocketmine\nbt\ReaderTracker;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\Tag;

class AbstractListTag extends Tag
{
	private int $type;
	private int $length;
	private int $current = 0;
	private AbstractNBTSerializer $reader;
	private ReaderTracker $tracker;

	public static function read(AbstractNBTSerializer $reader, ReaderTracker $tracker): self
	{
		$result = new self;
		$result->type = $reader->readByte();
		$result->length = $reader->readInt();
		$result->reader = clone $reader;
		$result->tracker = $tracker;

		for ($i = 0; $i < $result->length; $i++) {
			AbstractNBT::skipTag($result->type, $reader);
		}

		return $result;
	}

	public function getValue()
	{
		throw new BadMethodCallException("Abstract list cannot be read at once");
	}

	public function hasNext(): bool
	{
		return $this->current < $this->length;
	}

	public function next(): Tag
	{
		if (++$this->current > $this->length) {
			throw new NbtDataException("No more tags left to read");
		}

		$return = null;
		$tracker = $this->tracker;
		$reader = $this->reader;
		$type = $this->type;
		$tracker->protectDepth(static function () use ($reader, $tracker, $type, &$return) {
			$return = AbstractNBT::createTag($type, $reader, $tracker);
		});
		if ($return === null) {
			throw new NbtDataException("Failed to read tag");
		}
		return $return;
	}

	public function mustGetInt(): int
	{
		$tag = $this->next();
		if ($tag instanceof IntTag) {
			return $tag->getValue();
		}
		throw new NbtDataException("Expected IntTag, got " . get_class($tag));
	}

	public function getType(): int
	{
		return NBT::TAG_ByteArray;
	}

	public function write(NbtStreamWriter $writer): void
	{
		throw new \RuntimeException("unimplemented");
	}

	/**
	 * @return int
	 */
	public function getTagType(): int
	{
		return $this->type;
	}

	/**
	 * @return int
	 */
	public function getLength(): int
	{
		return $this->length;
	}

	public function toListTag(): ListTag
	{
		$result = [];
		for ($i = 0; $i < $this->length; $i++) {
			$result[] = $this->next();
		}
		return new ListTag($result, $this->type);
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