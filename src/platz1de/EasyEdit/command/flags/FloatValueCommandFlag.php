<?php

namespace platz1de\EasyEdit\command\flags;

class FloatValueCommandFlag extends SingularCommandFlag
{
	private float $value;

	/**
	 * @param string        $name
	 * @param float         $value
	 * @param string[]|null $aliases
	 * @param string|null   $id
	 */
	public function __construct(string $name, float $value, array $aliases = null, string $id = null)
	{
		$this->value = $value;
		parent::__construct($name, $aliases, $id);
	}

	/**
	 * @return float
	 */
	public function getArgument(): float
	{
		return $this->value;
	}
}