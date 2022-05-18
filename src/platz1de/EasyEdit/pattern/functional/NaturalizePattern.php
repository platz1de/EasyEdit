<?php

namespace platz1de\EasyEdit\pattern\functional;

use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\world\ChunkController;
use platz1de\EasyEdit\world\HeightMapCache;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;

class NaturalizePattern extends Pattern
{
	private Pattern $surface;
	private Pattern $ground;
	private Pattern $deep;

	/**
	 * @param Pattern|null $surface
	 * @param Pattern|null $ground
	 * @param Pattern|null $deep
	 */
	public function __construct(?Pattern $surface = null, ?Pattern $ground = null, ?Pattern $deep = null)
	{
		$this->surface = $surface ?? StaticBlock::from(VanillaBlocks::GRASS());
		$this->ground = $ground ?? StaticBlock::from(VanillaBlocks::DIRT());
		$this->deep = $deep ?? StaticBlock::from(VanillaBlocks::STONE());
		parent::__construct([]);
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
		return !in_array($iterator->getBlock($x, $y, $z) >> Block::INTERNAL_METADATA_BITS, HeightMapCache::getIgnore(), true);
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
		HeightMapCache::load($iterator, $current);
		return match (HeightMapCache::searchAirUpwards($x, $y, $z)) {
			1 => $this->surface->getFor($x, $y, $z, $iterator, $current, $total),
			2, 3 => $this->ground->getFor($x, $y, $z, $iterator, $current, $total),
			default => $this->deep->getFor($x, $y, $z, $iterator, $current, $total),
		};
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
		$stream->putString($this->surface->fastSerialize());
		$stream->putString($this->ground->fastSerialize());
		$stream->putString($this->deep->fastSerialize());
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->surface = Pattern::fastDeserialize($stream->getString());
		$this->ground = Pattern::fastDeserialize($stream->getString());
		$this->deep = Pattern::fastDeserialize($stream->getString());
	}
}