<?php

namespace platz1de\EasyEdit\pattern\logic\relation;

use platz1de\EasyEdit\pattern\block\BlockType;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\world\ChunkController;
use pocketmine\math\Axis;
use pocketmine\math\Vector3;

class HorizontalPattern extends Pattern
{
	private BlockType $block;

	/**
	 * @param BlockType $block
	 * @param array     $pieces
	 */
	public function __construct(BlockType $block, array $pieces)
	{
		parent::__construct($pieces);
		$this->block = $block;
	}

	/**
	 * @param int             $x
	 * @param int             $y
	 * @param int             $z
	 * @param ChunkController $iterator
	 * @param Selection       $current
	 * @param Selection       $total
	 * @return bool
	 */
	public function isValidAt(int $x, int $y, int $z, ChunkController $iterator, Selection $current, Selection $total): bool
	{
		foreach ((new Vector3($x, $y, $z))->sidesAroundAxis(Axis::Y) as $side) {
			if ($this->block->equals($iterator->getBlock($side->getFloorX(), $side->getFloorY(), $side->getFloorZ()))) {
				return true;
			}
		}

		return false;
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->block->fastSerialize());
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->block = BlockType::fastDeserialize($stream->getString());
	}
}