<?php

namespace platz1de\EasyEdit\selection\identifier;

interface SelectionIdentifier
{
	public function toIdentifier(): StoredSelectionIdentifier;
}