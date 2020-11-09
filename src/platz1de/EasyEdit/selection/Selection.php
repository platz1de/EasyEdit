<?php

namespace platz1de\EasyEdit\selection;

use pocketmine\level\format\Chunk;
use pocketmine\level\Position;
use Serializable;

abstract class Selection implements Serializable
{
	/**
	 * @var string
	 */
	protected $player;

	/**
	 * Selection constructor.
	 * @param string $player
	 */
	public function __construct(string $player)
	{
		$this->player = $player;
	}

	/**
	 * @param Position $place
	 * @return Chunk[]
	 */
	abstract public function getNeededChunks(Position $place): array;

	/**
	 * @return string
	 */
	public function getPlayer(): string
	{
		return $this->player;
	}

	public function close(): void
	{
	}
}