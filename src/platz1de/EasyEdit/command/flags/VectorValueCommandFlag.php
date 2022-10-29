<?php

namespace platz1de\EasyEdit\command\flags;

use pocketmine\math\Vector3;

class VectorValueCommandFlag extends SingularCommandFlag
{
	private Vector3 $value;

	public function __construct(string $name, Vector3 $value, array $aliases = null, string $id = null)
	{
		$this->value = $value;
		parent::__construct($name, $aliases, $id);
	}

	public function getArgument(): Vector3
	{
		return $this->value;
	}
}