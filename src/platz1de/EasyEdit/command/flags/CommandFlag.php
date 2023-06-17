<?php

namespace platz1de\EasyEdit\command\flags;

use platz1de\EasyEdit\session\Session;

abstract class CommandFlag
{
	private string $id;

	/**
	 * @param string      $name
	 * @param string[]    $aliases
	 * @param string|null $id
	 */
	final public function __construct(private string $name, private array $aliases = [], string $id = null)
	{
		$this->id = $id ?? $name[0];
	}

	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @return string[]
	 */
	public function getAliases(): array
	{
		return $this->aliases;
	}

	public function getId(): string
	{
		return $this->id;
	}

	abstract public function needsArgument(): bool;

	abstract public function parseArgument(Session $session, string $argument): self;

	public function fits(string $argument): bool
	{
		return true;
	}
}