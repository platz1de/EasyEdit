<?php

namespace platz1de\EasyEdit\thread\input\task;

use platz1de\EasyEdit\thread\input\InputData;
use platz1de\EasyEdit\thread\ThreadData;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class CancelTaskData extends InputData
{
	public static function from(): void
	{
		$data = new self();
		$data->send();
	}

	public function handle(): void
	{
		ThreadData::requirePause();
	}

	public function putData(ExtendedBinaryStream $stream): void { }

	public function parseData(ExtendedBinaryStream $stream): void { }
}