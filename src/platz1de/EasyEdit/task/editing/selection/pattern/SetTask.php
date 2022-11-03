<?php

namespace platz1de\EasyEdit\task\editing\selection\pattern;

use Generator;
use platz1de\EasyEdit\pattern\functional\GravityPattern;
use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
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
		$undo = $handler->getChanges();
		$undo->setPos1($undo->getPos1()->withComponents(null, $minY, null));
		$undo->setPos2($undo->getPos2()->withComponents(null, $maxY, null));
	}

	/**
	 * @return Generator<ShapeConstructor>
	 */
	public function prepareConstructors(): Generator
	{
		$selection = $this->selection;
		$pattern = $this->getPattern();
		$updateHeightMap = $pattern->contains(GravityPattern::class);
		yield $selection->asShapeConstructors(function (int $x, int $y, int $z) use ($updateHeightMap, $pattern, $selection): void {
			$block = $pattern->getFor($x, $y, $z, $handler->getOrigin(), $selection);
			if ($block !== -1) {
				$handler->changeBlock($x, $y, $z, $block);
				if ($updateHeightMap) {
					HeightMapCache::setBlockAt($x, $y, $z, $block === 0);
				}
			}
		}, $this->context);
	}
}