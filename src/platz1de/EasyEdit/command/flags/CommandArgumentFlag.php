<?php

namespace platz1de\EasyEdit\command\flags;

class CommandArgumentFlag extends SingularCommandFlag
{
	private string $value;

	public function __construct(string $name, string $value, array $aliases = null, string $id = null)
	{
		$this->value = $value;
		parent::__construct($name, $aliases, $id);
	}

	public function getArgument() : string 
	{
		return $this->value;
	}
}