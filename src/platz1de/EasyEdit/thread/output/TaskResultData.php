<?php

namespace platz1de\EasyEdit\thread\output;

use platz1de\EasyEdit\handler\EditHandler;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class TaskResultData extends OutputData
{
	public function handle(): void
	{
		EditHandler::callback($this);
	}

	public function putData(ExtendedBinaryStream $stream): void { }

	public function parseData(ExtendedBinaryStream $stream): void { }
}