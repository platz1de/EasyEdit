<?php

namespace platz1de\EasyEdit\task\editing\selection\pattern;

use platz1de\EasyEdit\pattern\functional\GravityPattern;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\pattern\PatternWrapper;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\selection\cubic\CubicStaticUndo;
use platz1de\EasyEdit\task\editing\type\SettingNotifier;
use platz1de\EasyEdit\thread\input\TaskInputData;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\world\HeightMapCache;
use pocketmine\math\Vector3;
use pocketmine\world\Position;

class SetTask extends PatternedEditTask
{
	use CubicStaticUndo;
	use SettingNotifier;

	/**
	 * @param string                $owner
	 * @param string                $world
	 * @param AdditionalDataManager $data
	 * @param Selection             $selection
	 * @param Vector3               $position
	 * @param Vector3               $splitOffset
	 * @param Pattern               $pattern
	 * @return SetTask
	 */
	public static function from(string $owner, string $world, AdditionalDataManager $data, Selection $selection, Vector3 $position, Vector3 $splitOffset, Pattern $pattern): SetTask
	{
		$instance = new self($owner, $world, $data, $position);
		PatternedEditTask::initPattern($instance, $selection, $splitOffset, $pattern);
		return $instance;
	}

	/**
	 * @param Selection $selection
	 * @param Pattern   $pattern
	 * @param Position  $place
	 */
	public static function queue(Selection $selection, Pattern $pattern, Position $place): void
	{
		TaskInputData::fromTask(self::from($selection->getPlayer(), $selection->getWorldName(), new AdditionalDataManager(true, true), $selection, $place->asVector3(), Vector3::zero(), $pattern));
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "set";
	}

	/**
	 * @param EditTaskHandler $handler
	 */
	public function executeEdit(EditTaskHandler $handler): void
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
		}, $pattern->getSelectionContext(), $this->getTotalSelection());
		$undo = $handler->getChanges();
		$undo->setPos1($undo->getPos1()->withComponents(null, $minY, null));
		$undo->setPos2($undo->getPos2()->withComponents(null, $maxY, null));
	}
}