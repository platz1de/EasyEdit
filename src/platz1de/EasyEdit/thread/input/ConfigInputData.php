<?php

namespace platz1de\EasyEdit\thread\input;

use platz1de\EasyEdit\utils\ConfigManager;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

/**
 * Some config values are needed on the edit thread
 */
class ConfigInputData extends InputData
{
	public static function create(): void
	{
		$data = new self();
		$data->send();
	}

	public function handle(): void
	{
		ConfigManager::distributeData();
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		ConfigManager::putRawData($stream);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		ConfigManager::parseRawData($stream);
	}
}