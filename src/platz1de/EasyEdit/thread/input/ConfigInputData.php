<?php

namespace platz1de\EasyEdit\thread\input;

use platz1de\EasyEdit\schematic\BlockConvertor;
use platz1de\EasyEdit\utils\ConfigManager;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\world\HeightMapCache;

/**
 * Some config values are needed on the edit thread
 */
class ConfigInputData extends InputData
{
	/**
	 * @var int[]
	 */
	private array $terrainIgnored;
	private int $fastSetMax;
	private int $pathfindingMax;
	private string $bedrockConversionDataSource;
	private string $bedrockPaletteDataSource;
	private string $javaPaletteDataSource;
	private string $rotationDataSource;
	private string $flipDataSource;
	private string $tileDataStatesSource;
	private bool $sendDebug;

	/**
	 * @param int[] $terrainIgnored
	 */
	public static function from(array $terrainIgnored, int $fastSetMax, int $pathfindingMax, string $bedrockConversionDataSource, string $bedrockPaletteDataSource, string $javaPaletteDataSource, string $rotationDataSource, string $flipDataSource, string $tileDataStatesSource, bool $sendDebug): void
	{
		$data = new self();
		$data->terrainIgnored = $terrainIgnored;
		$data->fastSetMax = $fastSetMax;
		$data->pathfindingMax = $pathfindingMax;
		$data->bedrockConversionDataSource = $bedrockConversionDataSource;
		$data->bedrockPaletteDataSource = $bedrockPaletteDataSource;
		$data->javaPaletteDataSource = $javaPaletteDataSource;
		$data->rotationDataSource = $rotationDataSource;
		$data->flipDataSource = $flipDataSource;
		$data->tileDataStatesSource = $tileDataStatesSource;
		$data->sendDebug = $sendDebug;
		$data->send();
	}

	public function handle(): void
	{
		HeightMapCache::setIgnore($this->terrainIgnored);
		BlockConvertor::load($this->bedrockConversionDataSource, $this->bedrockPaletteDataSource, $this->javaPaletteDataSource, $this->rotationDataSource, $this->flipDataSource, $this->tileDataStatesSource);
		ConfigManager::sendDebug($this->sendDebug);
		ConfigManager::setFastSetMax($this->fastSetMax);
		ConfigManager::setPathfindingMax($this->pathfindingMax);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putInt(count($this->terrainIgnored));
		foreach ($this->terrainIgnored as $id) {
			$stream->putInt($id);
		}
		$stream->putInt($this->fastSetMax);
		$stream->putInt($this->pathfindingMax);
		$stream->putString($this->bedrockConversionDataSource);
		$stream->putString($this->bedrockPaletteDataSource);
		$stream->putString($this->javaPaletteDataSource);
		$stream->putString($this->rotationDataSource);
		$stream->putString($this->flipDataSource);
		$stream->putString($this->tileDataStatesSource);
		$stream->putBool($this->sendDebug);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$count = $stream->getInt();
		for ($i = 0; $i < $count; $i++) {
			$this->terrainIgnored[] = $stream->getInt();
		}
		$this->fastSetMax = $stream->getInt();
		$this->pathfindingMax = $stream->getInt();
		$this->bedrockConversionDataSource = $stream->getString();
		$this->bedrockPaletteDataSource = $stream->getString();
		$this->javaPaletteDataSource = $stream->getString();
		$this->rotationDataSource = $stream->getString();
		$this->flipDataSource = $stream->getString();
		$this->tileDataStatesSource = $stream->getString();
		$this->sendDebug = $stream->getBool();
	}
}