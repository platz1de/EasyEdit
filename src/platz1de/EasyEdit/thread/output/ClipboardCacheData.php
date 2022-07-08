<?php

namespace platz1de\EasyEdit\thread\output;

use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\session\SessionIdentifier;
use platz1de\EasyEdit\session\SessionManager;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use UnexpectedValueException;

class ClipboardCacheData extends OutputData
{
	private SessionIdentifier $owner;
	private StoredSelectionIdentifier $changeId;

	/**
	 * @param SessionIdentifier          $owner
	 * @param ?StoredSelectionIdentifier $changeId
	 */
	public static function from(SessionIdentifier $owner, ?StoredSelectionIdentifier $changeId): void
	{
		if ($changeId === null) {
			throw new UnexpectedValueException("Clipboard should never be filled with invalid selections");
		}
		$data = new self();
		$data->owner = $owner;
		$data->changeId = $changeId;
		$data->send();
	}

	public function handle(): void
	{
		SessionManager::get($this->owner)->setClipboard($this->changeId);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putSessionIdentifier($this->owner);
		$stream->putString($this->changeId->fastSerialize());
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->owner = $stream->getSessionIdentifier();
		$this->changeId = StoredSelectionIdentifier::fastDeserialize($stream->getString());
	}
}