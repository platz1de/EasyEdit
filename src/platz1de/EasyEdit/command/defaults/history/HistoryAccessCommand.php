<?php

namespace platz1de\EasyEdit\command\defaults\history;

use Generator;
use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\flags\CommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\flags\IntegerCommandFlag;
use platz1de\EasyEdit\command\flags\SessionCommandFlag;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\utils\ConfigManager;

class HistoryAccessCommand extends EasyEditCommand
{
	private bool $type;

	public function __construct(bool $type)
	{
		$this->type = $type;
		parent::__construct($type ? "/undo" : "/redo", [KnownPermissions::PERMISSION_HISTORY, KnownPermissions::PERMISSION_EDIT]);
	}

	/**
	 * @param Session $session
	 * @return CommandFlag[]
	 */
	public function getKnownFlags(Session $session): array
	{
		return [
			"count" => new IntegerCommandFlag("count", [], "c"),
			"target" => new SessionCommandFlag("target", [], "t")
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
			} else {
				yield SessionCommandFlag::with($session, "target");
			}
		} else {
			if ($flags->hasFlag("target")) {
				$flags->removeFlag("target");
			}
			yield SessionCommandFlag::with($session, "target");
		}
		if (!$flags->hasFlag("count")) {
			yield $this->getKnownFlags($session)["count"]->parseArgument($this, $session, $args[0] ?? "1");
		}
	}

	public function process(Session $session, CommandFlagCollection $flags): void
	{
		$target = $flags->getSessionFlag("target");
		if ($this->type ? !$target->canUndo() : !$target->canRedo()) {
			$session->sendMessage($this->type ? "no-history" : "no-future");
		}

		$count = min(100, $flags->getIntFlag("count"));

		for ($i = 0; $i < $count; $i++) {
			$this->type ? $target->undoStep($session) : $target->redoStep($session);
		}
	}
}