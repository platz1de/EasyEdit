<?php

namespace platz1de\EasyEdit\command\flags;

use UnexpectedValueException;

class CommandFlagCollection
{
	/**
	 * @var CommandFlag[]
	 */
	private array $flags;

	public function addFlag(CommandFlag $flag): void
	{
		if (isset($this->flags[$flag->getName()])) {
			throw new UnexpectedValueException("Flag " . $flag->getName() . " already exists");
		}
		$this->flags[$flag->getName()] = $flag;
	}

	public function getStringFlag(string $name): string
	{
		$flag = $this->flags[$name];
		if (!$flag instanceof StringCommandFlag) {
			throw new UnexpectedValueException("Flag is of wrong type " . get_class($flag) . ", expected String");
		}
		return $flag->getArgument();
	}

	public function getIntFlag(string $name): int
	{
		$flag = $this->flags[$name];
		if (!$flag instanceof IntCommandFlag) {
			throw new UnexpectedValueException("Flag is of wrong type " . get_class($flag) . ", expected Integer");
		}
		return $flag->getArgument();
	}

	public function hasFlag(string $name): bool
	{
		return isset($this->flags[$name]);
	}
}