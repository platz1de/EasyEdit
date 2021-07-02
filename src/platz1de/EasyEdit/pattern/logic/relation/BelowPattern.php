<?php

namespace platz1de\EasyEdit\pattern\logic\relation;

use platz1de\EasyEdit\pattern\ParseError;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\utils\SafeSubChunkIteratorManager;
use pocketmine\level\Level;

class BelowPattern extends Pattern
{
	/**
	 * @param int                         $x
	 * @param int                         $y
	 * @param int                         $z
	 * @param SafeSubChunkIteratorManager $iterator
	 * @param Selection                   $selection
	 * @return bool
	 */
	public function isValidAt(int $x, int $y, int $z, SafeSubChunkIteratorManager $iterator, Selection $selection): bool
	{
		$y++;
		if ($y < Level::Y_MAX) {
			$iterator->moveTo($x, $y, $z);
			return ($iterator->getCurrent()->getBlockId($x & 0x0f, $y & 0x0f, $z & 0x0f) === $this->args[0]->getId()) && ($this->args[0]->getDamage() === -1 || $iterator->getCurrent()->getBlockData($x & 0x0f, $y & 0x0f, $z & 0x0f) === $this->args[0]->getDamage());
		}
		return false;
	}

	public function check(): void
	{
		try {
			$this->args[0] = Pattern::getBlockType($this->args[0] ?? "");
		} catch (ParseError $error) {
			throw new ParseError("Below needs a block as first Argument, " . ($this->args[0] ?? "") . " given");
		}
	}
}