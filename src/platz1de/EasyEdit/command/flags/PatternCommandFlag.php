<?php

namespace platz1de\EasyEdit\command\flags;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\exception\PatternParseException;
use platz1de\EasyEdit\pattern\parser\ParseError;
use platz1de\EasyEdit\pattern\parser\PatternParser;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\session\Session;

class PatternCommandFlag extends CommandFlag
{
	private Pattern $argument;

	/**
	 * @param Pattern     $argument
	 * @param string      $name
	 * @param string[]    $aliases
	 * @param string|null $id
	 * @return PatternCommandFlag
	 */
	public static function with(Pattern $argument, string $name, array $aliases = null, string $id = null): self
	{
		$flag = new self($name, $aliases, $id);
		$flag->hasArgument = true;
		$flag->argument = $argument;
		return $flag;
	}

	/**
	 * @param Pattern $argument
	 */
	public function setArgument(Pattern $argument): void
	{
		$this->argument = $argument;
	}

	/**
	 * @return Pattern
	 */
	public function getArgument(): Pattern
	{
		return $this->argument;
	}

	/**
	 * @param EasyEditCommand $command
	 * @param Session         $session
	 * @param string          $argument
	 * @return CommandFlag
	 */
	public function parseArgument(EasyEditCommand $command, Session $session, string $argument): CommandFlag
	{
		try {
			$this->setArgument(PatternParser::parseInput($argument, $session->asPlayer()));
			return $this;
		} catch (ParseError $exception) {
			throw new PatternParseException($exception);
		}
	}
}