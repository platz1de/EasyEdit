<?php

namespace platz1de\EasyEdit\pattern\block;

use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\pattern\PatternArgumentData;
use platz1de\EasyEdit\pattern\WrongPatternUsageException;
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
	 * @param Selection            $selection
	 * @return Block|null
	 */
	public function getFor(int $x, int $y, int $z, SafeSubChunkExplorer $iterator, Selection $selection): ?Block
	{
		return $this->args->getRealBlock();
	}

	/**
	 * @return int
	 */
	public function getId(): int
	{
		return $this->args->getRealBlock()->getId();
	}

	/**
	 * @return int
	 */
	public function getMeta(): int
	{
		return $this->args->getRealBlock()->getMeta();
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
		return new static([], PatternArgumentData::create()->setRealBlock($block));
	}

	/**
	 * @param int $fullBlock
	 * @return bool
	 */
	public function equals(int $fullBlock): bool
	{
		return $fullBlock === $this->args->getRealBlock()->getFullId();
	}

	public function getSelectionContext(): int
	{
		return SelectionContext::FULL;
	}
}