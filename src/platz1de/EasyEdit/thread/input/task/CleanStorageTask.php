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
	 * @return CleanStorageTask
	 */
	public static function from(array $storageIds): CleanStorageTask
	{
		$data = new self();
		$data->storageIds = $storageIds;
		return $data;
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