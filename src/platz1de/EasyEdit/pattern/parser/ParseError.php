<?php

namespace platz1de\EasyEdit\pattern\parser;

use UnexpectedValueException;

class ParseError extends UnexpectedValueException
{
	private string $rawMessage;
	private ?int $pos;
	private bool $priority;

	/**
	 * ParseError constructor.
	 * @param string   $message
	 * @param int|null $pos
	 * @param bool     $prefix
	 * @param bool     $priority
	 */
	public function __construct(string $message, ?int $pos = null, bool $prefix = true, bool $priority = false)
	{
		$this->rawMessage = ($prefix ? "Parse Error: " : "") . $message;
		$this->pos = $pos;
		parent::__construct($this->rawMessage . ($pos === null ? "" : " at Character " . $pos));
		$this->priority = $priority;
	}

	public function offset(int $offset): ParseError
	{
		return new self($this->rawMessage, ($this->pos ?? 0) + $offset, false);
	}

	/**
	 * @return bool
	 */
	public function isPriority(): bool
	{
		return $this->priority;
	}
}