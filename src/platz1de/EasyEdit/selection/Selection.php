<?php

namespace platz1de\EasyEdit\selection;

use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use Serializable;

abstract class Selection implements Serializable
{
	/**
	 * @var string
	 */
	protected $player;
	/**
	 * @var Level|string
	 */
	protected $level;

	/**
	 * Selection constructor.
	 * @param string $player
	 * @param Level  $level
	 */
	public function __construct(string $player, Level $level)
	{
		$this->player = $player;
		$this->level = $level;
	}

	/**
	 * @return Vector3[]
	 */
	abstract public function getAffectedBlocks(): array;

	/**
	 * @param Vector3 $place
	 * @return Chunk[]
	 */
	abstract public function getNeededChunks(Vector3 $place): array;

	/**
	 * @return string
	 */
	public function getPlayer(): string
	{
		return $this->player;
	}

	/**
	 * @return Level
	 */
	public function getLevel(): Level
	{
		return $this->level;
	}

	public function close(): void
	{
	}
}