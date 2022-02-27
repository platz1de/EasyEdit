<?php

namespace platz1de\EasyEdit\thread\output;

use platz1de\EasyEdit\cache\HistoryCache;
use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class HistoryCacheData extends OutputData
{
	private string $player;
	private StoredSelectionIdentifier $changeId;
	private bool $isUndo;

	/**
	 * @param string                         $player
	 * @param StoredSelectionIdentifier|null $changeId
	 * @param bool                           $isUndo
	 */
	public static function from(string $player, ?StoredSelectionIdentifier $changeId, bool $isUndo): void
	{
		if ($changeId === null) {
			EditThread::getInstance()->getLogger()->debug("Not saving history");
			return;
		}
		$data = new self();
		$data->player = $player;
		$data->changeId = $changeId;
		$data->isUndo = $isUndo;
		$data->send();
	}

	public function handle(): void
	{
		HistoryCache::addToCache($this->player, $this->changeId, $this->isUndo);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->player);
		$stream->putString($this->changeId->fastSerialize());
		$stream->putBool($this->isUndo);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->player = $stream->getString();
		$this->changeId = StoredSelectionIdentifier::fastDeserialize($stream->getString());
		$this->isUndo = $stream->getBool();
	}
}