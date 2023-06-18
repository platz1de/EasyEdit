<?php

namespace platz1de\EasyEdit\result;

use platz1de\EasyEdit\selection\identifier\BlockListSelectionIdentifier;
use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class CuttingTaskResult extends EditTaskResult
{
	/**
	 * @param int                          $affected
	 * @param BlockListSelectionIdentifier $selection
	 * @param BlockListSelectionIdentifier $clipboard
	 */
	public function __construct(int $affected, BlockListSelectionIdentifier $selection, private BlockListSelectionIdentifier $clipboard)
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
	 * @return BlockListSelectionIdentifier
	 */
	public function getClipboard(): BlockListSelectionIdentifier
	{
		return $this->clipboard;
	}
}