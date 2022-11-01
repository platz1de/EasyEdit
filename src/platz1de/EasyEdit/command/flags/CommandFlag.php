<?php

namespace platz1de\EasyEdit\command\flags;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\session\Session;

abstract class CommandFlag
{
	private string $name;
	/**
	 * @var string[]
	 */
	private array $aliases;
	private string $id;

	/**
	 * @param string      $name
	 * @param string[]    $aliases
	 * @param string|null $id
	 */
	final public function __construct(string $name, array $aliases = null, string $id = null)
	{
		$this->name = $name;
		$this->aliases = $aliases ?? [];
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

	abstract public function parseArgument(EasyEditCommand $command, Session $session, string $argument): self;
}