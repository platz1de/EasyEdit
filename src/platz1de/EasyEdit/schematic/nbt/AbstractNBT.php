<?php

namespace platz1de\EasyEdit\schematic\nbt;

use InvalidArgumentException;
use pocketmine\nbt\NBT;
use pocketmine\nbt\NbtDataException;
use pocketmine\nbt\NbtStreamReader;
use pocketmine\nbt\ReaderTracker;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\Tag;

class AbstractNBT extends NBT
{
	public static function createTag(int $type, NbtStreamReader $reader, ReaderTracker $tracker): Tag
	{
		if (!$reader instanceof AbstractNBTSerializer) {
			throw new InvalidArgumentException("Abstract nbt can only be constructed from abstract serializer");
		}
		switch ($type) {
			case self::TAG_ByteArray:
				return AbstractByteArrayTag::read($reader);
			case self::TAG_List:
				return AbstractListTag::read($reader, $tracker);
			case self::TAG_Compound:
				$result = new CompoundTag();
				$tracker->protectDepth(static function () use ($reader, $tracker, $result): void {
					for ($type = $reader->readByte(); $type !== NBT::TAG_End; $type = $reader->readByte()) {
						$name = $reader->readString();
						$tag = AbstractNBT::createTag($type, $reader, $tracker);
						$result->setTag($name, $tag);
					}
				});
				return $result;
		}
		return parent::createTag($type, $reader, $tracker);
	}

	public static function skipTag(int $type, AbstractNBTSerializer $reader): void
	{
		switch ($type) {
			case self::TAG_Byte:
				$reader->skip(1);
				return;
			case self::TAG_Short:
				$reader->skip(2);
				return;
			case self::TAG_Int:
			case self::TAG_Float:
				$reader->skip(4);
				return;
			case self::TAG_Long:
			case self::TAG_Double:
				$reader->skip(8);
				return;
			case self::TAG_ByteArray:
				$reader->skip($reader->readInt());
				return;
			case self::TAG_String:
				$reader->skip($reader->readShort());
				return;
			case self::TAG_List:
				$type = $reader->readByte();
				$count = $reader->readInt();
				for ($i = 0; $i < $count; $i++) {
					self::skipTag($type, $reader);
				}
				return;
			case self::TAG_Compound:
				for ($t = $reader->readByte(); $t !== NBT::TAG_End; $t = $reader->readByte()) {
					$reader->skip($reader->readShort()); //tag name
					self::skipTag($t, $reader);
				}
				return;
			case self::TAG_IntArray:
				$reader->skip($reader->readInt() * 4);
				return;
			default:
				throw new NbtDataException("Unknown NBT tag type $type");
		}
	}

	public static function isAbstract(Tag $tag): bool
	{
		return $tag instanceof AbstractByteArrayTag || $tag instanceof AbstractListTag || $tag instanceof CompoundTag;
	}

	public static function fromAbstractTile(CompoundTag|null $tag): CompoundTag|null
	{
		if ($tag === null) {
			return null;
		}
		$ret = self::fromAbstract($tag);
		if (!$ret instanceof CompoundTag) {
			throw new NbtDataException("Abstract tile must be compound");
		}
		return $ret;
	}

	public static function fromAbstract(Tag $tag): Tag
	{
		if ($tag instanceof CompoundTag) {
			foreach ($tag->getValue() as $key => $value) {
				if (self::isAbstract($value)) {
					$tag->setTag($key, self::fromAbstract($value));
				}
			}
		}
		if ($tag instanceof AbstractByteArrayTag) {
			$tag = $tag->toByteArrayTag();
		}
		if ($tag instanceof AbstractListTag) {
			$tag = $tag->toListTag();
			foreach ($tag->getValue() as $key => $value) {
				if (self::isAbstract($value)) {
					$tag->set($key, self::fromAbstract($value));
					break;
				}
			}
		}
		return $tag;
	}
}