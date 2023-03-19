<?php

namespace platz1de\EasyEdit\thread\output;

use platz1de\EasyEdit\convert\BlockStateConvertor;
use platz1de\EasyEdit\convert\TileConvertor;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class ResourceData extends OutputData
{
	public function __construct(private string $rawJTB, private string $rawBTJ) {}

	public function handle(): void
	{
		TileConvertor::load();
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->rawJTB);
		$stream->putString($this->rawBTJ);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		BlockStateConvertor::loadResourceData($stream->getString(), $stream->getString());
	}
}