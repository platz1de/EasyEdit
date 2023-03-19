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
	 * @var T
	 */
	private mixed $default;
	private bool $isDefault = false;

	/**
	 * @param T           $default
	 * @param string      $name
	 * @param string[]    $aliases
	 * @param string|null $id
	 * @return static
	 */
	public static function default(mixed $default, string $name, array $aliases = [], string $id = null): static
	{
		$instance = new static($name, $aliases, $id);
		$instance->default = $default;
		return $instance;
	}

	/**
	 * @param T           $argument
	 * @param string      $name
	 * @param string[]    $aliases
	 * @param string|null $id
	 * @param bool        $default
	 * @return static
	 */
	public static function with(mixed $argument, string $name, array $aliases = [], string $id = null, bool $default = false): static
	{
		$instance = new static($name, $aliases, $id);
		$instance->argument = $argument;
		$instance->isDefault = $default;
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

	/**
	 * @return bool
	 */
	public function hasDefault(): bool
	{
		return isset($this->default) || $this->isDefault;
	}

	/**
	 * @return static
	 */
	public function asDefault(): static
	{
		if ($this->isDefault) {
			return $this;
		}
		$this->argument = $this->default;
		return $this;
	}
}