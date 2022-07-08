<?php

namespace platz1de\EasyEdit\thread\output;

use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\session\SessionIdentifier;
use platz1de\EasyEdit\session\SessionManager;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class HistoryCacheData extends OutputData
{
	private SessionIdentifier $owner;
	private StoredSelectionIdentifier $changeId;
	private bool $isUndo;

	/**
	 * @param SessionIdentifier              $owner
	 * @param StoredSelectionIdentifier|null $changeId
	 * @param bool                           $isUndo
	 */
	public static function from(SessionIdentifier $owner, ?StoredSelectionIdentifier $changeId, bool $isUndo): void
	{
		if ($changeId === null) {
			EditThread::getInstance()->getLogger()->debug("Not saving history");
			return;
		}
		$data = new self();
		$data->owner = $owner;
		$data->changeId = $changeId;
		$data->isUndo = $isUndo;
		$data->send();
	}

	public function handle(): void
	{
		SessionManager::get($this->owner)->addToHistory($this->changeId, $this->isUndo);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putSessionIdentifier($this->owner);
		$stream->putString($this->changeId->fastSerialize());
		$stream->putBool($this->isUndo);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->owner = $stream->getSessionIdentifier();
		$this->changeId = StoredSelectionIdentifier::fastDeserialize($stream->getString());
		$this->isUndo = $stream->getBool();
	}
}