<?php

namespace platz1de\EasyEdit\thread\output;

use platz1de\EasyEdit\schematic\BlockConvertor;
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
		$stream->putString(BlockConvertor::getResourceData());
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		BlockConvertor::loadResourceData($stream->getString());
	}
}