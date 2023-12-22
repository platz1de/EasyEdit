<?php

namespace platz1de\EasyEdit\result;

use platz1de\EasyEdit\selection\identifier\BlockListSelectionIdentifier;
use platz1de\EasyEdit\selection\identifier\SelectionSerializer;
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
		$stream->putString(SelectionSerializer::fastSerialize($this->clipboard));
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);
		$this->clipboard = SelectionSerializer::mustGetBlockList($stream->getString());
	}

	public function storeSelections(): void
	{
		parent::storeSelections();
		$this->clipboard = $this->clipboard->toIdentifier();
	}

	/**
	 * @return BlockListSelectionIdentifier
	 */
	public function getClipboard(): BlockListSelectionIdentifier
	{
		return $this->clipboard;
	}
}