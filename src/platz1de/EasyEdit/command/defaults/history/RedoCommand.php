<?php

namespace platz1de\EasyEdit\command\defaults\history;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\session\SessionManager;
use platz1de\EasyEdit\utils\ConfigManager;

class RedoCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/redo", [KnownPermissions::PERMISSION_HISTORY, KnownPermissions::PERMISSION_EDIT]);
	}

	/**
	 * @param Session  $session
	 * @param string[] $args
	 */
	public function process(Session $session, array $args): void
	{
		if (isset($args[0]) && !is_numeric($args[0]) && ConfigManager::isAllowingOtherHistory() && $session->asPlayer()->hasPermission(KnownPermissions::PERMISSION_HISTORY_OTHER)) {
			$target = SessionManager::get($args[0], false);
			array_shift($args);
		} else {
			$target = $session;
		}

		if (!$target->canRedo()) {
			Messages::send($session->getPlayer(), "no-future");
		}

		$count = min(100, (int) ($args[0] ?? 1));

		for ($i = 0; $i < $count; $i++) {
			$target->redoStep($session->getIdentifier());
		}
	}
}