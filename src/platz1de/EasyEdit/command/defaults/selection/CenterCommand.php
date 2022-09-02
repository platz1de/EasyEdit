<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\selection\SelectionContext;

class CenterCommand extends AliasedContextCommand
{
	public function __construct()
	{
		parent::__construct(SelectionContext::center(), "/center", ["/middle"]);
	}
}