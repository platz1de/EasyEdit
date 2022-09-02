<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\selection\SelectionContext;

class WallCommand extends AliasedContextCommand
{
	public function __construct()
	{
		parent::__construct(SelectionContext::walls(), "/walls", ["/wall"]);
	}
}