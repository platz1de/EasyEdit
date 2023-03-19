<?php

namespace platz1de\EasyEdit\thread\output\session;

use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class HistoryCacheData extends SessionOutputData
{
	/**
	 * @param StoredSelectionIdentifier $changeId
	 * @param bool                      $isUndo
	 */
	public function __construct(private StoredSelectionIdentifier $changeId, private bool $isUndo) {}

	public function checkSend(): bool
	{
		if (!$this->changeId->isValid()) {
			EditThread::getInstance()->debug("Not saving history");
			return false;
		}
		return true;
	}

	public function handleSession(Session $session): void
	{
		$session->addToHistory($this->changeId, $this->isUndo);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->changeId->fastSerialize());
		$stream->putBool($this->isUndo);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->changeId = StoredSelectionIdentifier::fastDeserialize($stream->getString());
		$this->isUndo = $stream->getBool();
	}
}