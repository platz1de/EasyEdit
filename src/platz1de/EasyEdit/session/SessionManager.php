<?php

namespace platz1de\EasyEdit\session;

use BadMethodCallException;
use pocketmine\player\Player;

class SessionManager
{
	/**
	 * @var Session[]
	 */
	private static array $sessions = [];

	/**
	 * @param Player|SessionIdentifier|string $player
	 * @param bool                            $create
	 * @return Session
	 */
	public static function get(Player|SessionIdentifier|string $player, bool $create = true): Session
	{
		if ($player instanceof Player) {
			$player = $player->getName();
		}
		if ($player instanceof SessionIdentifier) {
			if ($player->isPlayer()) {
				$player = $player->getName();
			} else {
				throw new BadMethodCallException("Session can only be created for players, plugins or internal use should use tasks directly");
			}
		}
		return self::$sessions[$player] ?? ($create ? (self::$sessions[$player] = new Session(new SessionIdentifier(true, $player))) : throw new BadMethodCallException("Session not found"));
	}
}