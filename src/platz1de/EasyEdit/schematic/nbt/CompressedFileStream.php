<?php

namespace platz1de\EasyEdit\schematic\nbt;

use pocketmine\utils\BinaryStream;

class CompressedFileStream extends BinaryStream
{
	private CompressedFileReader $file;

	public function __construct(string $fileName)
	{
		$this->file = new CompressedFileReader($fileName);

		parent::__construct();
	}

	public function get(int $len): string
	{
		$ret = $this->file->get($len, $this->offset);
		$this->offset += $len;
		return $ret;
	}

	public function setOffset(int $offset): void
	{
		$this->offset = $offset;
	}

	public function close(): void
	{
		$this->file->close();
	}

	/**
	 * Optimizes the stream for high-frequency access.
	 * Clones the file reader to avoid seeking back and forth.
	 *
	 * Make sure you manually call {@link close()} on this after you are done with it.
	 */
	public function optimizeHighFrequencyAccess(): void
	{
		$this->file = clone $this->file;
	}
}