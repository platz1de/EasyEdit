<?php

namespace platz1de\EasyEdit\task\editing\selection\cubic;

use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\StaticBlockListSelection;

trait CubicStaticUndo
{
	abstract public function getWorld(): string;

	abstract public function getSelection(): Selection;

	/**
	 * @return StaticBlockListSelection
	 */
	public function createUndoBlockList(): BlockListSelection
	{
		return new StaticBlockListSelection($this->getWorld(), $this->getSelection()->getPos1(), $this->getSelection()->getPos2());
	}
}