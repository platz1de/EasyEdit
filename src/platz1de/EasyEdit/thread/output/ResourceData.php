<?php

namespace platz1de\EasyEdit\thread\output;

use platz1de\EasyEdit\convert\BlockStateConvertor;
use platz1de\EasyEdit\thread\input\RuntimeInputData;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class ResourceData extends OutputData
{
	public function handle(): void
	{
		RuntimeInputData::create();
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString(BlockStateConvertor::getResourceData());
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		BlockStateConvertor::loadResourceData($stream->getString());
	}
}