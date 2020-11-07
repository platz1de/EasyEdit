<?php

namespace platz1de\EasyEdit\task;

use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\level\SimpleChunkManager;
use pocketmine\Server;
use UnexpectedValueException;

class ReferencedChunkManager extends SimpleChunkManager
{
	/**
	 * @var string
	 */
	private $level;

	public function __construct(string $level)
	{
		parent::__construct(0);
		$this->level = $level;
	}

	/**
	 * @return Chunk[]
	 */
	public function getChunks(): array
	{
		return $this->chunks;
	}

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
		if($level === null){
			throw new UnexpectedValueException("Level " . $this->getLevelName() . " was deleted, unloaded or renamed");
		}
		return $level;
	}
}