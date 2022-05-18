<?php

namespace platz1de\EasyEdit\pattern\block;

use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\world\ChunkController;
use pocketmine\block\Block;

class StaticBlock extends BlockType
{
	private int $id;

	/**
	 * @param int $id
	 */
	public function __construct(int $id)
	{
		parent::__construct();
		$this->id = $id;
	}

	/**
	 * @param Block $block
	 * @return StaticBlock
	 */
	public static function from(Block $block): StaticBlock
	{
		return new self($block->getFullId());
	}

	/**
	 * @param int             $x
	 * @param int             $y
	 * @param int             $z
	 * @param ChunkController $iterator
	 * @param Selection       $current
	 * @param Selection       $total
	 * @return int
	 */
	public function getFor(int $x, int &$y, int $z, ChunkController $iterator, Selection $current, Selection $total): int
	{
		return $this->id;
	}

	/**
	 * @return int
	 */
	public function get(): int
	{
		return $this->id;
	}

	/**
	 * @return int
	 */
	public function getId(): int
	{
		return $this->id >> Block::INTERNAL_METADATA_BITS;
	}

	/**
	 * @return int
	 */
	public function getMeta(): int
	{
		return $this->id & Block::INTERNAL_METADATA_MASK;
	}

	/**
	 * @param int $fullBlock
	 * @return bool
	 */
	public function equals(int $fullBlock): bool
	{
		return $fullBlock === $this->id;
	}

	/**
	 * @param SelectionContext $context
	 */
	public function applySelectionContext(SelectionContext $context): void
	{
		$context->includeWalls()->includeVerticals()->includeFilling();
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putInt($this->id);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->id = $stream->getInt();
	}
}