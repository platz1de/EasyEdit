<?php

namespace platz1de\EasyEdit\pattern\logic\relation;

use platz1de\EasyEdit\pattern\block\BlockType;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\world\ChunkController;

class AbovePattern extends Pattern
{
	private BlockType $block;

	/**
	 * @param BlockType $block
	 * @param Pattern[] $pieces
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
		$y--;
		if ($y >= 0) {
			return $this->block->equals($iterator->getBlock($x, $y, $z));
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