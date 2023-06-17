<?php

namespace platz1de\EasyEdit\command;

use Generator;
use platz1de\EasyEdit\command\exception\InvalidUsageException;
use platz1de\EasyEdit\command\flags\CommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\flags\ValuedCommandFlag;
use platz1de\EasyEdit\session\Session;

trait FlagArgumentParser
{
	/** @var array<string, bool> */
	private array $flagOrder = [];

	/**
	 * @param Session $session
	 * @return CommandFlag[]
	 */
	abstract public function getKnownFlags(Session $session): array;

	/**
	 * @param CommandFlagCollection $flags
	 * @param Session               $session
	 * @param string[]              $args
	 * @return Generator<CommandFlag>
	 */
	public function parseArguments(CommandFlagCollection $flags, Session $session, array $args): Generator
	{
		$known = $this->getKnownFlags($session);
		$i = 0;
		foreach ($this->flagOrder as $flag => $needed) {
			if (!$flags->hasFlag($flag)) {
				if (isset($args[$i]) && $known[$flag]->fits($args[$i])) {
					yield $known[$flag]->needsArgument() ? $known[$flag]->parseArgument($session, $args[$i]) : $known[$flag];
				} elseif ($needed) {
					throw new InvalidUsageException();
				}
			}
			$i++;
		}
		foreach ($this->getKnownFlags($session) as $flag) {
			if ($flag instanceof ValuedCommandFlag && $flag->hasDefault() && !$flags->hasFlag($flag->getName())) {
				yield $flag->asDefault();
			}
		}
	}
}