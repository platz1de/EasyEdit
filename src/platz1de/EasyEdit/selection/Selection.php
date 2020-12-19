<?php

namespace platz1de\EasyEdit\selection;

use platz1de\EasyEdit\task\WrongSelectionTypeError;
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

	/**
	 * @param Selection $selection
	 * @param string    $expected
	 * @throws WrongSelectionTypeError
	 */
	public static function validate(Selection $selection, string $expected): void
	{
		if (get_class($selection) !== $expected) {
			throw new WrongSelectionTypeError(get_class($selection), $expected);
		}
	}

	/**
	 * @param int $blockId
	 * @return bool
	 */
	public static function processBlock(int &$blockId): bool
	{
		$return = ($blockId !== 0);

		if ($blockId === 217) {
			$blockId = 0;
		}

		return $return;
	}
}