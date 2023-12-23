<?php

namespace platz1de\EasyEdit\result;

use platz1de\EasyEdit\selection\identifier\BlockListSelectionIdentifier;
use platz1de\EasyEdit\selection\identifier\SelectionSerializer;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class SelectionManipulationResult extends TaskResult
{
	/**
	 * @param int                          $changed
	 * @param BlockListSelectionIdentifier $result
	 */
	public function __construct(private int $changed, private BlockListSelectionIdentifier $result) { }

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putInt($this->changed);
		$stream->putString(SelectionSerializer::fastSerialize($this->result));
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->changed = $stream->getInt();
		$this->result = SelectionSerializer::mustGetBlockList($stream->getString());
	}

	public function storeSelections(): void
	{
		$this->result = $this->result->toIdentifier();
	}

	/**
	 * @return int
	 */
	public function getChanged(): int
	{
		return $this->changed;
	}

	/**
	 * @return BlockListSelectionIdentifier
	 */
	public function getSelection(): BlockListSelectionIdentifier
	{
		return $this->result;
	}
}