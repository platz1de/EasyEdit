<?php

namespace platz1de\EasyEdit\thread\output;

use platz1de\EasyEdit\selection\ClipBoardManager;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class ClipboardCacheData extends OutputData
{
	private string $player;
	private int $changeId;

	/**
	 * @param string $player
	 * @param int    $changeId
	 */
	public static function from(string $player, int $changeId): void
	{
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
		$stream->putInt($this->changeId);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->player = $stream->getString();
		$this->changeId = $stream->getInt();
	}
}