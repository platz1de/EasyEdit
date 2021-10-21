<?php

namespace platz1de\EasyEdit\thread\input\task;

use platz1de\EasyEdit\thread\input\InputData;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class CleanStorageTask extends InputData
{
	/**
	 * @var int[]
	 */
	private array $storageIds = [];

	/**
	 * @param int[] $storageIds
	 */
	public static function from(array $storageIds): void
	{
		$data = new self();
		$data->storageIds = $storageIds;
		$data->send();
	}

	public function handle(): void
	{
		foreach ($this->storageIds as $id) {
			StorageModule::cleanStored($id);
		}
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putInt(count($this->storageIds));
		foreach ($this->storageIds as $id) {
			$stream->putInt($id);
		}
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$count = $stream->getInt();
		for ($i = 0; $i < $count; $i++) {
			$this->storageIds[] = $stream->getInt();
		}
	}
}