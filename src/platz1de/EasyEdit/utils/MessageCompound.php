<?php

namespace platz1de\EasyEdit\utils;

class MessageCompound
{
	/**
	 * @var MessageComponent[]
	 */
	private array $components = [];

	public function __construct(private string $divisor = "\n") {}

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