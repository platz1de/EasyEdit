<?php

namespace platz1de\EasyEdit\command\flags;

class SetValueCommandFlag extends SingularCommandFlag
{
	private int $value;

	public function __construct(string $name, int $value, array $aliases = null, string $id = null)
	{
		$this->value = $value;
		parent::__construct($name, $aliases, $id);
	}

	public function getArgument() : int 
	{
		return $this->value;
	}
}