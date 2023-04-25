<?php

namespace platz1de\EasyEdit\result;

use platz1de\EasyEdit\selection\identifier\SelectionIdentifier;
use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class CuttingTaskResult extends EditTaskResult
{
	/**
	 * @param int                 $affected
	 * @param SelectionIdentifier $selection
	 * @param SelectionIdentifier $clipboard
	 */
	public function __construct(int $affected, SelectionIdentifier $selection, private SelectionIdentifier $clipboard)
	{
		parent::__construct($affected, $selection);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);
		$stream->putString($this->clipboard->toIdentifier()->fastSerialize());
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);
		$this->clipboard = StoredSelectionIdentifier::fastDeserialize($stream->getString());
	}

	/**
	 * @return SelectionIdentifier
	 */
	public function getClipboard(): SelectionIdentifier
	{
		return $this->clipboard;
	}
}