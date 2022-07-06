<?php

namespace platz1de\EasyEdit\schematic\nbt;

use BadMethodCallException;
use pocketmine\utils\BinaryDataException;
use pocketmine\utils\BinaryStream;

class CompressedFileStream extends BinaryStream
{
	/**
	 * @var resource
	 */
	private $stream;
	private string $fileName;

	public function __construct(string $fileName)
	{
		$this->fileName = $fileName;
		$file = gzopen($fileName, "r");

		if ($file === false) {
			throw new BadMethodCallException("Failed to open file " . $fileName);
		}

		$this->stream = $file;
		parent::__construct();
	}

	public function get(int $len): string
	{
		if ($len === 0) {
			return "";
		}

		if (feof($this->stream)) {
			throw new BinaryDataException("Reached end of file, need $len bytes");
		}

		$r = gzread($this->stream, $len);

		if ($r === false) {
			throw new BinaryDataException("Failed to read $len bytes");
		}

		return $r;
	}

	public function setOffset(int $offset): void
	{
		gzseek($this->stream, $offset);
	}

	public function getOffset(): int
	{
		$offset = gztell($this->stream);
		if ($offset === false) {
			throw new BinaryDataException("Failed to get offset");
		}
		return $offset;
	}

	public function close(): void
	{
		gzclose($this->stream);
	}

	public function __clone(): void
	{
		$offset = gztell($this->stream);
		$this->stream = gzopen($this->fileName, "r");
		gzseek($this->stream, $offset);
	}
}