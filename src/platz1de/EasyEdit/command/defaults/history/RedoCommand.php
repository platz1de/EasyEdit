<?php

namespace platz1de\EasyEdit\command\defaults\history;

use Generator;
use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\flags\CommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\flags\IntCommandFlag;
use platz1de\EasyEdit\command\flags\SessionArgumentFlag;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\utils\ConfigManager;

class RedoCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/redo", [KnownPermissions::PERMISSION_HISTORY, KnownPermissions::PERMISSION_EDIT]);
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		$target = $flags->getSessionFlag("target");
		if (!$target->canRedo()) {
			$session->sendMessage("no-future");
		}

		$count = min(100, $flags->getIntFlag("count"));

		for ($i = 0; $i < $count; $i++) {
			$target->redoStep($session);
		}
	}

	/**
	 * @param Session $session
	 * @return CommandFlag[]
	 */
	public function getKnownFlags(Session $session): array
	{
		return [
			"count" => new IntCommandFlag("count", [], "c"),
			"target" => new SessionArgumentFlag("target", [], "t")
		];
	}

	/**
	 * @param CommandFlagCollection $flags
	 * @param Session               $session
	 * @param string[]              $args
	 * @return Generator<CommandFlag>
	 */
	public function parseArguments(CommandFlagCollection $flags, Session $session, array $args): Generator
	{
		if (ConfigManager::isAllowingOtherHistory() && $session->asPlayer()->hasPermission(KnownPermissions::PERMISSION_HISTORY_OTHER)) {
			if (isset($args[0]) && !is_numeric($args[0])) {
				yield $this->getKnownFlags($session)["target"]->parseArgument($this, $session, $args[0]);
				array_shift($args);
			}
		} else {
			if ($flags->hasFlag("target")) {
				$flags->removeFlag("target");
			}
			yield $this->getKnownFlags($session)["target"]->parseArgument($this, $session, $session->getPlayer());
		}
		if (!$flags->hasFlag("count")) {
			yield $this->getKnownFlags($session)["count"]->parseArgument($this, $session, $args[0] ?? "1");
		}
	}
}