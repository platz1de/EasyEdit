<?php

namespace platz1de\EasyEdit\thread\output;

use platz1de\EasyEdit\convert\BlockStateConvertor;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class ResourceData extends OutputData
{
	private string $rawJTB;
	private string $rawBTJ;

	public function __construct(string $rawJTB, string $rawBTJ)
	{
		$this->rawJTB = $rawJTB;
		$this->rawBTJ = $rawBTJ;
	}

	public function handle(): void {}

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