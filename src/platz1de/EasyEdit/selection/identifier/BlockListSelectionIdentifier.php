<?php

namespace platz1de\EasyEdit\selection\identifier;

interface BlockListSelectionIdentifier extends SelectionIdentifier
{
	public function toIdentifier(): StoredSelectionIdentifier;
}