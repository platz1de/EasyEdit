<?php

namespace platz1de\EasyEdit\schematic\nbt;

use BadMethodCallException;
use pocketmine\utils\BinaryDataException;

class CompressedFileReader
{
	/**
	 * @var resource
	 */
	private $stream;
	private int $offset = 0;

	public function __construct(private string $fileName)
	{
		$file = gzopen($fileName, "r");

		if ($file === false) {
			throw new BadMethodCallException("Failed to open file " . $fileName);
		}

		$this->stream = $file;
	}

	public function get(int $len, int $offset): string
	{
		if ($len === 0) {
			return "";
		}

		if ($offset !== $this->offset) {
			gzseek($this->stream, $offset);
			$this->offset = $offset;
		}

		if (feof($this->stream)) {
			throw new BinaryDataException("Reached end of file, need $len bytes");
		}

		$r = gzread($this->stream, $len);

		if ($r === false) {
			throw new BinaryDataException("Failed to read $len bytes");
		}

		$this->offset += $len;

		return $r;
	}

	public function close(): void
	{
		gzclose($this->stream);
	}

	public function __clone(): void
	{
		$file = gzopen($this->fileName, "r");
		if ($file === false) {
			throw new BadMethodCallException("Failed to open file " . $this->fileName);
		}
		$this->stream = $file;
	}
}