<?php

namespace platz1de\EasyEdit\task\selection\cubic;

use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\StaticBlockListSelection;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use pocketmine\math\Vector3;

trait CubicStaticUndo
{
	/**
	 * @param Selection             $selection
	 * @param Vector3               $place
	 * @param string                $level
	 * @param AdditionalDataManager $data
	 * @return StaticBlockListSelection
	 */
	public function getUndoBlockList(Selection $selection, Vector3 $place, string $level, AdditionalDataManager $data): BlockListSelection
	{
		return new StaticBlockListSelection($selection->getPlayer(), $level, $selection->getCubicStart(), $selection->getCubicEnd());
	}
}