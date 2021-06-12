<?php

namespace platz1de\EasyEdit\utils;

use Generator;
use pocketmine\math\Vector3;
use pocketmine\utils\Binary;
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
		$this->putInt($vector->getX());
		$this->putInt($vector->getY());
		$this->putInt($vector->getZ());
	}

	/**
	 * @return Vector3
	 */
	public function getVector(): Vector3
	{
		return new Vector3($this->getInt(), $this->getInt(), $this->getInt());
	}
}