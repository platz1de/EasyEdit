<?php

namespace platz1de\EasyEdit\utils;

use platz1de\EasyEdit\math\BaseVector;
use platz1de\EasyEdit\math\BlockOffsetVector;
use platz1de\EasyEdit\math\BlockVector;
use platz1de\EasyEdit\math\OffGridBlockVector;
use platz1de\EasyEdit\session\SessionIdentifier;
use pocketmine\math\Vector3;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\utils\BinaryStream;

class ExtendedBinaryStream extends BinaryStream
{
	/**
	 * @param string $str
	 */
	public function putString(string $str): void
	{
		$this->putInt(strlen($str));
		$this->put($str);
	}

	/**
	 * @return string
	 */
	public function getString(): string
	{
		return $this->get($this->getInt());
	}

	/**
	 * @param Vector3 $vector
	 */
	public function putVector(Vector3 $vector): void
	{
		//TODO: Remove...
		$this->putInt($vector->getFloorX());
		$this->putInt($vector->getFloorY());
		$this->putInt($vector->getFloorZ());
	}

	/**
	 * @return Vector3
	 */
	public function getVector(): Vector3
	{
		return new Vector3($this->getInt(), $this->getInt(), $this->getInt());
	}

	public function putBlockVector(BaseVector $vector): void
	{
		$this->putInt($vector->x);
		$this->putInt($vector->y);
		$this->putInt($vector->z);
	}

	public function getBlockVector(): BlockVector
	{
		return new BlockVector($this->getInt(), $this->getInt(), $this->getInt());
	}

	public function getOffGridBlockVector(): OffGridBlockVector
	{
		return new OffGridBlockVector($this->getInt(), $this->getInt(), $this->getInt());
	}

	public function getOffsetVector(): BlockOffsetVector
	{
		return new BlockOffsetVector($this->getInt(), $this->getInt(), $this->getInt());
	}

	/**
	 * @param CompoundTag $compound
	 */
	public function putCompound(CompoundTag $compound): void
	{
		$this->putString((new LittleEndianNbtSerializer())->write(new TreeRoot($compound)));
	}

	/**
	 * @return CompoundTag
	 */
	public function getCompound(): CompoundTag
	{
		return (new LittleEndianNbtSerializer())->read($this->getString())->mustGetCompoundTag();
	}

	/**
	 * @param CompoundTag[] $compounds
	 */
	public function putCompounds(array $compounds): void
	{
		$this->putInt(count($compounds));
		foreach ($compounds as $hash => $compound) {
			$this->putLong($hash);
			$this->putCompound($compound);
		}
	}

	/**
	 * @return CompoundTag[]
	 */
	public function getCompounds(): array
	{
		$compounds = [];
		$count = $this->getInt();
		for ($i = 0; $i < $count; $i++) {
			$compounds[$this->getLong()] = $this->getCompound();
		}
		return $compounds;
	}

	/**
	 * @param SessionIdentifier $id
	 */
	public function putSessionIdentifier(SessionIdentifier $id): void
	{
		$this->putString($id->fastSerialize());
	}

	/**
	 * @return SessionIdentifier
	 */
	public function getSessionIdentifier(): SessionIdentifier
	{
		return SessionIdentifier::fastDeserialize($this->getString());
	}

	/**
	 * @param string[] $array
	 * @return string
	 */
	public static function fastArraySerialize(array $array): string
	{
		$stream = new ExtendedBinaryStream();
		$stream->putInt(count($array));
		foreach ($array as $str) {
			$stream->putString($str);
		}
		return $stream->getBuffer();
	}

	/**
	 * @param string $str
	 * @return string[] $array
	 */
	public static function fastArrayDeserialize(string $str): array
	{
		$array = [];
		$stream = new ExtendedBinaryStream($str);
		$count = $stream->getInt();
		for ($i = 0; $i < $count; $i++) {
			$array[] = $stream->getString();
		}
		return $array;
	}
}