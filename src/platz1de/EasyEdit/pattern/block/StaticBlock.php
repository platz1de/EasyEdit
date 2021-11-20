<?php

namespace platz1de\EasyEdit\pattern\block;

use platz1de\EasyEdit\pattern\parser\WrongPatternUsageException;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\pattern\PatternArgumentData;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\utils\SafeSubChunkExplorer;
use pocketmine\block\Block;
use Throwable;

class StaticBlock extends Pattern
{
	/**
	 * @param int                  $x
	 * @param int                  $y
	 * @param int                  $z
	 * @param SafeSubChunkExplorer $iterator
	 * @param Selection            $current
	 * @param Selection            $total
	 * @return int
	 */
	public function getFor(int $x, int $y, int $z, SafeSubChunkExplorer $iterator, Selection $current, \platz1de\EasyEdit\selection\Selection $total): int
	{
		return $this->args->getRealBlock();
	}

	/**
	 * @return int
	 */
	public function getId(): int
	{
		return $this->args->getRealBlock() >> Block::INTERNAL_METADATA_BITS;
	}

	/**
	 * @return int
	 */
	public function getMeta(): int
	{
		return $this->args->getRealBlock() & Block::INTERNAL_METADATA_MASK;
	}

	public function check(): void
	{
		try {
			//shut up phpstorm
			$this->args->setRealBlock($this->args->getRealBlock());
		} catch (Throwable) {
			throw new WrongPatternUsageException("StaticBlock needs a block as first Argument");
		}
	}

	/**
	 * @param Block $block
	 * @return StaticBlock
	 */
	public static function from(Block $block): StaticBlock
	{
		return new static([], PatternArgumentData::create()->setRealBlock($block->getFullId()));
	}

	/**
	 * @param int $fullBlock
	 * @return bool
	 */
	public function equals(int $fullBlock): bool
	{
		return $fullBlock === $this->args->getRealBlock();
	}

	/**
	 * @param SelectionContext $context
	 */
	public function applySelectionContext(SelectionContext $context): void
	{
		$context->includeWalls()->includeVerticals()->includeFilling();
	}
}