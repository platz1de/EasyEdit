<?php

namespace platz1de\EasyEdit\thread\input;

use platz1de\EasyEdit\schematic\BlockConvertor;
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
	private string $conversionDataSource;

	/**
	 * @param int[] $heightIgnored
	 */
	public static function from(array $heightIgnored, string $conversionDataSource): void
	{
		$data = new self();
		$data->heightIgnored = $heightIgnored;
		$data->conversionDataSource = $conversionDataSource;
		$data->send();
	}

	public function handle(): void
	{
		HeightMapCache::setIgnore($this->heightIgnored);
		BlockConvertor::load($this->conversionDataSource);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putInt(count($this->heightIgnored));
		foreach ($this->heightIgnored as $id) {
			$stream->putInt($id);
		}
		$stream->putString($this->conversionDataSource);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$count = $stream->getInt();
		for ($i = 0; $i < $count; $i++) {
			$this->heightIgnored[] = $stream->getInt();
		}
		$this->conversionDataSource = $stream->getString();
	}
}