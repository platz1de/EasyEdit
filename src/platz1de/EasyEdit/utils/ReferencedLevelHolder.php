<?php

namespace platz1de\EasyEdit\utils;

use pocketmine\world\World;
use pocketmine\Server;
use UnexpectedValueException;

trait ReferencedLevelHolder
{
	/**
	 * @var string
	 */
	protected $level;

	/**
	 * @return string
	 */
	public function getLevelName(): string
	{
		return $this->level;
	}

	/**
	 * @return Level
	 */
	public function getLevel(): Level
	{
		$level = Server::getInstance()->getLevelByName($this->getLevelName());
		if ($level === null) {
			throw new UnexpectedValueException("Level " . $this->getLevelName() . " was deleted, unloaded or renamed");
		}
		return $level;
	}
}