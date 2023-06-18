<?php

namespace platz1de\EasyEdit\selection\identifier;

use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class StoredSelectionIdentifier implements BlockListSelectionIdentifier
{
	private bool $delete = false;
	private Selection $selectionCache;

	/**
	 * @param int $id
	 */
	final public function __construct(private int $id) { }

	public static function invalid(): StoredSelectionIdentifier
	{
		return new self(0);
	}

	public function isValid(): bool
	{
		return $this->id !== 0;
	}

	public function toIdentifier(): StoredSelectionIdentifier
	{
		return $this;
	}

	public function asSelection(): Selection
	{
		if (!isset($this->selectionCache)) {
			$this->selectionCache = StorageModule::getStored($this);
		}
		return $this->selectionCache;
	}

	public function fastSerialize(): string
	{
		$stream = new ExtendedBinaryStream();
		$stream->putInt($this->id);
		$stream->putBool($this->delete);
		return $stream->getBuffer();
	}

	public static function fastDeserialize(string $data): static
	{
		$stream = new ExtendedBinaryStream($data);
		$id = new static($stream->getInt());
		$id->delete = $stream->getBool();
		return $id;
	}

	/**
	 * @return int
	 */
	public function getMagicId(): int
	{
		return $this->id;
	}

	public function markForDeletion(): StoredSelectionIdentifier
	{
		$this->delete = true;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isOneTime(): bool
	{
		return $this->delete;
	}
}