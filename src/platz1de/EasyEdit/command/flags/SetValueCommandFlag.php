<?php

namespace platz1de\EasyEdit\command\flags;

class SetValueCommandFlag extends SingularCommandFlag
{
	public function __construct(string $name, array $aliases = null, string $id = null)
	{
		parent::__construct($name, $aliases, $id);
	}
}