<?php

namespace platz1de\EasyEdit\command\flags;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\session\Session;

class StringCommandFlag extends CommandFlag
{
	private string $argument;

	/**
	 * @param string      $argument
	 * @param string      $name
	 * @param string[]    $aliases
	 * @param string|null $id
	 * @return StringCommandFlag
	 */
	public static function with(string $argument, string $name, array $aliases = null, string $id = null): self
	{
		$flag = new self($name, $aliases, $id);
		$flag->hasArgument = true;
		$flag->argument = $argument;
		return $flag;
	}

	/**
	 * @param string $argument
	 */
	public function setArgument(string $argument): void
	{
		$this->argument = $argument;
	}

	/**
	 * @return string
	 */
	public function getArgument(): string
	{
		return $this->argument;
	}

	/**
	 * @param EasyEditCommand $command
	 * @param Session         $session
	 * @param string          $argument
	 * @return StringCommandFlag
	 */
	public function parseArgument(EasyEditCommand $command, Session $session, string $argument): self
	{
		$this->setArgument($argument);
		return $this;
	}
}