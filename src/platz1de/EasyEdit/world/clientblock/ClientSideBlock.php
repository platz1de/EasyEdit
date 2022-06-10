<?php

namespace platz1de\EasyEdit\world\clientblock;

use pocketmine\player\Player;

abstract class ClientSideBlock
{
	private int $id;

	private static int $ids = 0;

	public function __construct()
	{
		$this->id = self::$ids++;
	}

	abstract public function send(Player $player): void;

	abstract public function remove(Player $player): void;

	abstract public function checkResend(Player $player): void;

	abstract public function update(Player $player): void;

	/**
	 * @return int
	 */
	public function getId(): int
	{
		return $this->id;
	}
}