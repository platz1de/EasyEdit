<?php

namespace platz1de\EasyEdit\utils;

use pocketmine\math\Vector3;
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