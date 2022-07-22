<?php

namespace platz1de\EasyEdit\utils;

/**
 * Allows translations consisting of a dynamic amount of other translations
 */
class MessageComponent
{
	private string $key;
	/**
	 * @var string[]|MessageComponent[]|MessageCompound[]
	 */
	private array $args;

	/**
	 * @param string                                        $key
	 * @param string[]|MessageComponent[]|MessageCompound[] $args
	 */
	public function __construct(string $key, array $args = [])
	{
		$this->key = $key;
		$this->args = $args;
	}

	/**
	 * @return string
	 */
	public function toString(): string
	{
		$args = [];
		foreach ($this->args as $key => $arg) {
			if ($arg instanceof self || $arg instanceof MessageCompound) {
				$args[$key] = $arg->toString();
			} else {
				$args[$key] = $arg;
			}
		}
		return Messages::replace($this->key, $args);
	}
}