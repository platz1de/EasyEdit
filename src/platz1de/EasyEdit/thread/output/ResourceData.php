<?php

namespace platz1de\EasyEdit\thread\output;

use platz1de\EasyEdit\convert\BedrockStatePreprocessor;
use platz1de\EasyEdit\convert\BlockStateConvertor;
use platz1de\EasyEdit\convert\TileConvertor;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\RepoManager;

class ResourceData extends OutputData
{
	public function __construct(private string $rawJTB, private string $rawBTJ) { }

	public function handle(): void { }

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->rawJTB);
		$stream->putString($this->rawBTJ);
		$stream->putString(BedrockStatePreprocessor::$rawData);
		$stream->putLong(RepoManager::getVersion());
		BedrockStatePreprocessor::$rawData = "";
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		BlockStateConvertor::loadResourceData($stream->getString(), $stream->getString());
		BedrockStatePreprocessor::loadResourceData($stream->getString());
		TileConvertor::load($stream->getLong());
	}
}