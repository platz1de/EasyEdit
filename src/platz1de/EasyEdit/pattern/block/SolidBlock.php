<?php

namespace platz1de\EasyEdit\pattern\block;

use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\world\HeightMapCache;
use platz1de\EasyEdit\world\SafeSubChunkExplorer;
use pocketmine\block\Block;
use pocketmine\utils\AssumptionFailedError;

class SolidBlock extends StaticBlock
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
	public function getFor(int $x, int &$y, int $z, SafeSubChunkExplorer $iterator, Selection $current, Selection $total): int
	{
		throw new AssumptionFailedError("Solid block group should only be used in comparison context");
	}

	/**
	 * @return int
	 */
	public function get(): int
	{
		throw new AssumptionFailedError("Solid block group should only be used in comparison context");
	}

	/**
	 * @return int
	 */
	public function getId(): int
	{
		throw new AssumptionFailedError("Solid block group should only be used in comparison context");
	}

	/**
	 * @return int
	 */
	public function getMeta(): int
	{
		throw new AssumptionFailedError("Solid block group should only be used in comparison context");
	}

	public function check(): void { }

	/**
	 * @return SolidBlock
	 */
	public static function create(): SolidBlock
	{
		$pattern = self::from([]);
		if (!$pattern instanceof self) {
			throw new AssumptionFailedError("SolidBlock was wrapped into a parent pattern while creating instance");
		}
		return $pattern;
	}

	/**
	 * @param int $fullBlock
	 * @return bool
	 */
	public function equals(int $fullBlock): bool
	{
		return !in_array($fullBlock >> Block::INTERNAL_METADATA_BITS, HeightMapCache::getIgnore(), true);
	}

	/**
	 * @param SelectionContext $context
	 */
	public function applySelectionContext(SelectionContext $context): void
	{
		throw new AssumptionFailedError("Solid block group should only be used in comparison context");
	}
}