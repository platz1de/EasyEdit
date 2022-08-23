<?php

namespace platz1de\EasyEdit\task\editing\selection\pattern;

use platz1de\EasyEdit\pattern\functional\GravityPattern;
use platz1de\EasyEdit\pattern\PatternWrapper;
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
	 * @param Vector3         $min
	 * @param Vector3         $max
	 */
	public function executeEdit(EditTaskHandler $handler, Vector3 $min, Vector3 $max): void
	{
		$selection = $this->getCurrentSelection();
		$pattern = PatternWrapper::wrap([$this->getPattern()]);
		$minY = $selection->getPos1()->getFloorY();
		$maxY = $selection->getPos2()->getFloorY();
		$updateHeightMap = $pattern->contains(GravityPattern::class);
		$selection->useOnBlocks(function (int $x, int $y, int $z) use ($updateHeightMap, &$maxY, &$minY, $handler, $pattern, $selection): void {
			$block = $pattern->getFor($x, $y, $z, $handler->getOrigin(), $selection, $this->getTotalSelection());
			if ($block !== -1) {
				$handler->changeBlock($x, $y, $z, $block);
				if ($updateHeightMap) {
					HeightMapCache::setBlockAt($x, $y, $z, $block === 0);
				}
				$minY = min($minY, $y);
				$maxY = max($maxY, $y);
			}
		}, $pattern->getSelectionContext(), $this->getTotalSelection(), $min, $max);
		$undo = $handler->getChanges();
		$undo->setPos1($undo->getPos1()->withComponents(null, $minY, null));
		$undo->setPos2($undo->getPos2()->withComponents(null, $maxY, null));
	}
}