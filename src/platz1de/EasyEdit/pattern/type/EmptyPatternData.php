<?php

namespace platz1de\EasyEdit\pattern\type;

use platz1de\EasyEdit\utils\ExtendedBinaryStream;

trait EmptyPatternData
{
	public function putData(ExtendedBinaryStream $stream): void { }

	public function parseData(ExtendedBinaryStream $stream): void { }
}