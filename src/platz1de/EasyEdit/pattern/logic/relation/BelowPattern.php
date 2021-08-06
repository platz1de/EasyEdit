<?php

namespace platz1de\EasyEdit\pattern\logic\relation;

use platz1de\EasyEdit\pattern\ParseError;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\utils\SafeSubChunkExplorer;
use pocketmine\block\Block;
use pocketmine\world\World;

class BelowPattern extends Pattern
{
	/**
	 * @param int                  $x
	 * @param int                  $y
	 * @param int                  $z
	 * @param SafeSubChunkExplorer $iterator
	 * @param Selection            $selection
	 * @return bool
	 */
	public function isValidAt(int $x, int $y, int $z, SafeSubChunkExplorer $iterator, Selection $selection): bool
	{
		$y++;
		if ($y < World::Y_MAX) {
			$iterator->moveTo($x, $y, $z);
			return ((($iterator->getCurrent()->getFullBlock($x & 0x0f, $y & 0x0f, $z & 0x0f) >> Block::INTERNAL_METADATA_BITS) === $this->args->getBlock()->getId()) && ($this->args->getBlock()->getMeta() === -1 || ($iterator->getCurrent()->getFullBlock($x & 0x0f, $y & 0x0f, $z & 0x0f) & Block::INTERNAL_METADATA_MASK) === $this->args->getBlock()->getMeta()));
		}
		return false;
	}

	public function check(): void
	{
		try {
			//shut up phpstorm
			$this->args->setBlock($this->args->getBlock());
		} catch (ParseError $error) {
			throw new ParseError("Below needs a block as first Argument");
		}
	}
}