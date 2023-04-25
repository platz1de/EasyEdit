<?php

namespace platz1de\EasyEdit\result;

use platz1de\EasyEdit\selection\identifier\SelectionIdentifier;
use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class EditTaskResult extends TaskResult
{
	/**
	 * @param int                 $affected
	 * @param SelectionIdentifier $selection Might be invalid, can be history or clipboard depending on the task
	 */
	public function __construct(private int $affected, private SelectionIdentifier $selection) {}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putInt($this->affected);
		$this->selection = $this->selection->toIdentifier();
		if ($this->selection->isValid()) {
			$stream->putBool(true);
			$stream->putString($this->selection->fastSerialize());
		} else {
			$stream->putBool(false);
		}
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->affected = $stream->getInt();
		if ($stream->getBool()) {
			$this->selection = StoredSelectionIdentifier::fastDeserialize($stream->getString());
		} else {
			$this->selection = StoredSelectionIdentifier::invalid();
		}
	}

	/**
	 * @return int
	 */
	public function getAffected(): int
	{
		return $this->affected;
	}

	/**
	 * @return SelectionIdentifier
	 */
	public function getSelection(): SelectionIdentifier
	{
		return $this->selection;
	}
}