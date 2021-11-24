<?php

namespace platz1de\EasyEdit\thread\output;

use platz1de\EasyEdit\cache\HistoryCache;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class HistoryCacheData extends OutputData
{
	private string $player;
	private int $changeId;
	private bool $isUndo;

	/**
	 * @param string $player
	 * @param int    $changeId
	 * @param bool   $isUndo
	 */
	public static function from(string $player, int $changeId, bool $isUndo): void
	{
		if ($changeId === -1) {
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
		$stream->putInt($this->changeId);
		$stream->putBool($this->isUndo);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->player = $stream->getString();
		$this->changeId = $stream->getInt();
		$this->isUndo = $stream->getBool();
	}
}