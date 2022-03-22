<?php

namespace platz1de\EasyEdit\thread\output;

use platz1de\EasyEdit\convert\BlockStateConvertor;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class ResourceData extends OutputData
{
	public static function from(): void
	{
		$data = new self();
		$data->send();
	}

	public function handle(): void
	{
		//nope
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