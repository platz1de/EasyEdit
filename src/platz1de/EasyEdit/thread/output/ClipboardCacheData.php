<?php

namespace platz1de\EasyEdit\thread\output;

use platz1de\EasyEdit\selection\ClipBoardManager;
use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use UnexpectedValueException;

class ClipboardCacheData extends OutputData
{
	private string $player;
	private StoredSelectionIdentifier $changeId;

	/**
	 * @param string                     $player
	 * @param ?StoredSelectionIdentifier $changeId
	 */
	public static function from(string $player, ?StoredSelectionIdentifier $changeId): void
	{
		if ($changeId === null) {
			throw new UnexpectedValueException("Clipboard should never be filled with invalid selections");
		}
		$data = new self();
		$data->player = $player;
		$data->changeId = $changeId;
		$data->send();
	}

	public function handle(): void
	{
		ClipBoardManager::setForPlayer($this->player, $this->changeId);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->player);
		$stream->putString($this->changeId->fastSerialize());
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->player = $stream->getString();
		$this->changeId = StoredSelectionIdentifier::fastDeserialize($stream->getString());
	}
}