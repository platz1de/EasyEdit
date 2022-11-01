<?php

namespace platz1de\EasyEdit\command;

use Generator;
use platz1de\EasyEdit\command\exception\InvalidUsageException;
use platz1de\EasyEdit\command\flags\CommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\flags\ValuedCommandFlag;
use platz1de\EasyEdit\session\Session;

abstract class SimpleFlagArgumentCommand extends EasyEditCommand
{
	/**
	 * @var bool[]
	 */
	private array $flagOrder;

	/**
	 * @param string   $name
	 * @param bool[]   $flagOrder
	 * @param string[] $permissions
	 * @param string[] $aliases
	 */
	public function __construct(string $name, array $flagOrder, array $permissions, array $aliases = [])
	{
		parent::__construct($name, $permissions, $aliases);
		$this->flagOrder = $flagOrder;
	}

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
				if (isset($args[$i])) {
					yield $known[$flag]->parseArgument($this, $session, $args[$i]);
				} elseif ($needed) {
					throw new InvalidUsageException($this);
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