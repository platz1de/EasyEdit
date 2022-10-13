<?php

namespace platz1de\EasyEdit\command\flags;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\exception\InvalidUsageException;
use platz1de\EasyEdit\command\exception\UnknownFlagException;
use platz1de\EasyEdit\session\Session;

class CommandFlagParser
{
	/**
	 * @param EasyEditCommand $command
	 * @param string[]        $args
	 * @param Session         $session
	 * @return CommandFlagCollection
	 */
	public static function parseFlags(EasyEditCommand $command, array $args, Session $session): CommandFlagCollection
	{
		$known = $command->getKnownFlags();
		$ids = [];
		foreach ($known as $flag) {
			foreach ($flag->getAliases() as $alias) {
				$known[$alias] = $flag;
			}
			$ids[$flag->getId()] = $flag;
		}
		$skip = false;
		$flags = new CommandFlagCollection();
		$legacy = [];
		foreach ($args as $i => $arg) {
			if (str_starts_with($arg, "-")) {
				if ($skip) {
					throw new InvalidUsageException($command);
				}
				if (str_starts_with($arg, "--")) {
					$name = strtolower(substr($arg, 2));
					if (!isset($known[$name])) {
						throw new UnknownFlagException(substr($arg, 2));
					}
					$flag = $known[$name];
					if (self::checkFlagArgument($flag, $args, $i, $command)) {
						$flag->parseArgument($command, $session, $args[$i + 1]);
						$skip = true;
					}
					$flags->addFlag($flag);
				} elseif (is_numeric(substr($arg, 1))) {
					continue; //Negative numbers are not flags
				} else {
					if ($arg === "-") {
						throw new UnknownFlagException("");
					}
					$list = str_split(strtolower(substr($arg, 1)));
					foreach ($list as $key => $f) {
						if (!isset($ids[$f])) {
							throw new UnknownFlagException(substr($arg, 2));
						}
						$flag = $ids[$arg];
						if (self::checkFlagArgument($flag, $args, $i, $command)) {
							if ($key !== array_key_last($list)) {
								throw new InvalidUsageException($command);
							}
							$flag->parseArgument($command, $session, $args[$i + 1]);
							$skip = true;
						}
						$flags->addFlag($flag);
					}
				}
			} else if ($skip) {
				$skip = false;
			} else {
				$legacy[] = $arg;
			}
		}
		foreach ($command->parseArguments($flags, $session, $legacy) as $flag) {
			$flags->addFlag($flag);
		}
		return $flags;
	}

	/**
	 * @param CommandFlag     $flag
	 * @param string[]        $args
	 * @param int             $i
	 * @param EasyEditCommand $command
	 * @return bool
	 */
	private static function checkFlagArgument(CommandFlag $flag, array $args, int $i, EasyEditCommand $command): bool
	{
		if ($flag->needsArgument()) {
			if (isset($args[$i + 1])) {
				return true;
			}

			throw new InvalidUsageException($command);
		}

		return false;
	}
}