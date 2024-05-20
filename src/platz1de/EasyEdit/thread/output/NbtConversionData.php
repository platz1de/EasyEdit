<?php

namespace platz1de\EasyEdit\thread\output;

use platz1de\EasyEdit\convert\ItemConvertor;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class NbtConversionData extends OutputData
{
	public function __construct(private string $rawConversionMap) { }

	public function handle(): void { }

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->rawConversionMap);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
        ItemConvertor::loadResourceData($stream->getString());
	}
}
