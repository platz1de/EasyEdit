<?php

namespace platz1de\EasyEdit\result;

use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class CuttingTaskResult extends EditTaskResult
{
	/**
	 * @param int                       $affected
	 * @param float                     $time
	 * @param StoredSelectionIdentifier $selection
	 * @param StoredSelectionIdentifier $clipboard
	 */
	public function __construct(int $affected, float $time, StoredSelectionIdentifier $selection, private StoredSelectionIdentifier $clipboard)
	{
		parent::__construct($affected, $time, $selection);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);
		$stream->putString($this->clipboard->fastSerialize());
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);
		$this->clipboard = StoredSelectionIdentifier::fastDeserialize($stream->getString());
	}

	/**
	 * @return StoredSelectionIdentifier
	 */
	public function getClipboard(): StoredSelectionIdentifier
	{
		return $this->clipboard;
	}
}