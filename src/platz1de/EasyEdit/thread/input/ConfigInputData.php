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
	private string $bedrockConversionDataSource;
	private string $bedrockPaletteDataSource;
	private string $javaPaletteDataSource;
	private string $rotationDataSource;

	/**
	 * @param int[] $heightIgnored
	 */
	public static function from(array $heightIgnored, string $bedrockConversionDataSource, string $bedrockPaletteDataSource, string $javaPaletteDataSource, string $rotationDataSource): void
	{
		$data = new self();
		$data->heightIgnored = $heightIgnored;
		$data->bedrockConversionDataSource = $bedrockConversionDataSource;
		$data->bedrockPaletteDataSource = $bedrockPaletteDataSource;
		$data->javaPaletteDataSource = $javaPaletteDataSource;
		$data->rotationDataSource = $rotationDataSource;
		$data->send();
	}

	public function handle(): void
	{
		HeightMapCache::setIgnore($this->heightIgnored);
		BlockConvertor::load($this->bedrockConversionDataSource, $this->bedrockPaletteDataSource, $this->javaPaletteDataSource, $this->rotationDataSource);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putInt(count($this->heightIgnored));
		foreach ($this->heightIgnored as $id) {
			$stream->putInt($id);
		}
		$stream->putString($this->bedrockConversionDataSource);
		$stream->putString($this->bedrockPaletteDataSource);
		$stream->putString($this->javaPaletteDataSource);
		$stream->putString($this->rotationDataSource);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$count = $stream->getInt();
		for ($i = 0; $i < $count; $i++) {
			$this->heightIgnored[] = $stream->getInt();
		}
		$this->bedrockConversionDataSource = $stream->getString();
		$this->bedrockPaletteDataSource = $stream->getString();
		$this->javaPaletteDataSource = $stream->getString();
		$this->rotationDataSource = $stream->getString();
	}
}