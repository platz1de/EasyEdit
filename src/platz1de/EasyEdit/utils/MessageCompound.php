<?php

namespace platz1de\EasyEdit\utils;

class MessageCompound
{
	private string $divisor;
	/**
	 * @var MessageComponent[]
	 */
	private array $components = [];

	public function __construct(string $divisor = "\n")
	{
		$this->divisor = $divisor;
	}

	/**
	 * @param MessageComponent $component
	 */
	public function addComponent(MessageComponent $component): void
	{
		$this->components[] = $component;
	}

	/**
	 * @return string
	 */
	public function toString(): string
	{
		$components = [];
		foreach ($this->components as $component) {
			$components[] = $component->toString();
		}
		return implode($this->divisor, $components);
	}
}