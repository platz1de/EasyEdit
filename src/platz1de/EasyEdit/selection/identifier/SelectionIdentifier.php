<?php

namespace platz1de\EasyEdit\selection\identifier;

use platz1de\EasyEdit\selection\Selection;

interface SelectionIdentifier
{
	public function fastSerialize(): string;

	public static function fastDeserialize(string $data): static;

	public function asSelection(): Selection;
}