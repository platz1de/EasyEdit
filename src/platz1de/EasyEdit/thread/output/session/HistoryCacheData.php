<?php

namespace platz1de\EasyEdit\thread\output\session;

use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class HistoryCacheData extends SessionOutputData
{
	private StoredSelectionIdentifier $changeId;
	private bool $isUndo;

	/**
	 * @param StoredSelectionIdentifier $changeId
	 * @param bool                      $isUndo
	 */
	public function __construct(StoredSelectionIdentifier $changeId, bool $isUndo)
	{
		$this->changeId = $changeId;
		$this->isUndo = $isUndo;
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