<?php

namespace platz1de\EasyEdit\task\editing\selection\pattern;

use platz1de\EasyEdit\pattern\functional\GravityPattern;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\selection\cubic\CubicStaticUndo;
use platz1de\EasyEdit\task\editing\type\SettingNotifier;
use platz1de\EasyEdit\world\HeightMapCache;
use pocketmine\math\Vector3;

class SetTask extends PatternedEditTask
{
	use CubicStaticUndo;
	use SettingNotifier;

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "set";
	}

	/**
	 * @param EditTaskHandler $handler
	 * @param int             $chunk
	 */
	public function executeEdit(EditTaskHandler $handler, int $chunk): void
	{
		$selection = $this->selection;
		$pattern = $this->getPattern();
		$minY = $selection->getPos1()->getFloorY();
		$maxY = $selection->getPos2()->getFloorY();
		$updateHeightMap = $pattern->contains(GravityPattern::class);
		$selection->useOnBlocks(function (int $x, int $y, int $z) use ($updateHeightMap, &$maxY, &$minY, $handler, $pattern, $selection): void {
			$block = $pattern->getFor($x, $y, $z, $handler->getOrigin(), $selection);
			if ($block !== -1) {
				$handler->changeBlock($x, $y, $z, $block);
				if ($updateHeightMap) {
					HeightMapCache::setBlockAt($x, $y, $z, $block === 0);
				}
				$minY = min($minY, $y);
				$maxY = max($maxY, $y);
			}
		}, $this->context, $chunk);
		$undo = $handler->getChanges();
		$undo->setPos1($undo->getPos1()->withComponents(null, $minY, null));
		$undo->setPos2($undo->getPos2()->withComponents(null, $maxY, null));
	}
}