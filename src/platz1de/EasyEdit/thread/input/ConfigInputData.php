<?php

namespace platz1de\EasyEdit\thread\input;

use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\HeightMapCache;

/**
 * Some config values are needed on the edit thread
 */
class ConfigInputData extends InputData
{
	/**
	 * @var int[]
	 */
	private array $heightIgnored;

	/**
	 * @param int[] $heightIgnored
	 */
	public static function from(array $heightIgnored): void
	{
		$data = new self();
		$data->heightIgnored = $heightIgnored;
		$data->send();
	}

	public function handle(): void
	{
		HeightMapCache::setIgnore($this->heightIgnored);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putInt(count($this->heightIgnored));
		foreach ($this->heightIgnored as $id) {
			$stream->putInt($id);
		}
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$count = $stream->getInt();
		for ($i = 0; $i < $count; $i++) {
			$this->heightIgnored[] = $stream->getInt();
		}
	}
}