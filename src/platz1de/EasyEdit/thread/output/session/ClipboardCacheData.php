<?php

namespace platz1de\EasyEdit\thread\output\session;

use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class ClipboardCacheData extends SessionOutputData
{
	/**
	 * @param StoredSelectionIdentifier $changeId
	 */
	public function __construct(private StoredSelectionIdentifier $changeId) {}

	public function handleSession(Session $session): void
	{
		$session->setClipboard($this->changeId);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->changeId->fastSerialize());
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->changeId = StoredSelectionIdentifier::fastDeserialize($stream->getString());
	}
}