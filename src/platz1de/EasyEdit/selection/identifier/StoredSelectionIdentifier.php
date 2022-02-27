<?php

namespace platz1de\EasyEdit\selection\identifier;

use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class StoredSelectionIdentifier
{
	private int $id;
	private string $type;

	/**
	 * @param int    $id
	 * @param string $type
	 */
	public function __construct(int $id, string $type)
	{
		$this->id = $id;
		$this->type = $type;
	}

	public function fastSerialize(): string
	{
		$stream = new ExtendedBinaryStream();
		$stream->putInt($this->id);
		$stream->putString($this->type);
		return $stream->getBuffer();
	}

	public static function fastDeserialize(string $data): StoredSelectionIdentifier
	{
		$stream = new ExtendedBinaryStream($data);
		return new StoredSelectionIdentifier($stream->getInt(), $stream->getString());
	}

	/**
	 * @return int
	 */
	public function getMagicId(): int
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getType(): string
	{
		return $this->type;
	}
}