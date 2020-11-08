<?php

namespace platz1de\EasyEdit\task;

use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\level\SimpleChunkManager;
use pocketmine\math\Vector3;
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

	/**
	 * @param Vector3 $pos
	 * @param int     $xSize
	 * @param int     $zSize
	 */
	public function load(Vector3 $pos, int $xSize, int $zSize): void
	{
		for ($x = $pos->getX() >> 4; $x <= ($pos->getX() + $xSize) >> 4; $x++) {
			for ($z = $pos->getZ() >> 4; $z <= ($pos->getZ() + $zSize) >> 4; $z++) {
				$this->setChunk($x, $z, ($chunk = new Chunk($x, $z)));
				$chunk->setGenerated();
			}
		}
	}
}