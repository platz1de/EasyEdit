<?php

namespace platz1de\EasyEdit\utils;

use pocketmine\world\World;
use pocketmine\Server;
use UnexpectedValueException;

trait ReferencedWorldHolder
{
	protected string $world;

	/**
	 * @return string
	 */
	public function getWorldName(): string
	{
		return $this->world;
	}

	/**
	 * @return World
	 */
	public function getWorld(): World
	{
		$world = Server::getInstance()->getWorldManager()->getWorldByName($this->getWorldName());
		if ($world === null) {
			throw new UnexpectedValueException("World " . $this->getWorldName() . " was deleted, unloaded or renamed");
		}
		return $world;
	}
}