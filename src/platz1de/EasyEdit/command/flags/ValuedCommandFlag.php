<?php

namespace platz1de\EasyEdit\command\flags;

/**
 * @template T
 */
abstract class ValuedCommandFlag extends CommandFlag
{
	/**
	 * @var T
	 */
	private mixed $argument;

	/**
	 * @param T           $argument
	 * @param string      $name
	 * @param string[]    $aliases
	 * @param string|null $id
	 * @return static
	 */
	public static function with(mixed $argument, string $name, array $aliases = null, string $id = null): static
	{
		$instance = new static($name, $aliases, $id);
		$instance->argument = $argument;
		return $instance;
	}

	public function needsArgument(): bool
	{
		return !isset($this->argument);
	}

	/**
	 * @param T $argument
	 */
	public function setArgument(mixed $argument): void
	{
		$this->argument = $argument;
	}

	/**
	 * @return T
	 */
	public function getArgument(): mixed
	{
		return $this->argument;
	}
}