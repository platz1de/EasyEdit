<?php

namespace platz1de\EasyEdit\task\editing\selection;

use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\selection\cubic\CubicStaticUndo;
use platz1de\EasyEdit\task\editing\type\SettingNotifier;
use platz1de\EasyEdit\thread\input\TaskInputData;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\world\HeightMapCache;
use pocketmine\math\Vector3;
use pocketmine\world\Position;

class SmoothTask extends SelectionEditTask
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
	 * @return SmoothTask
	 */
	public static function from(string $owner, string $world, AdditionalDataManager $data, Selection $selection, Vector3 $position, Vector3 $splitOffset): SmoothTask
	{
		$instance = new self($owner);
		SelectionEditTask::initSelection($instance, $world, $data, $selection, $position, $splitOffset);
		return $instance;
	}

	/**
	 * @param Selection $selection
	 * @param Position  $place
	 */
	public static function queue(Selection $selection, Position $place): void
	{
		TaskInputData::fromTask(self::from($selection->getPlayer(), $selection->getWorldName(), new AdditionalDataManager(true, true), $selection, $place->asVector3(), Vector3::zero()));
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "smooth";
	}

	public function executeEdit(EditTaskHandler $handler): void
	{
		HeightMapCache::load($handler->getOrigin(), $this->getCurrentSelection());
		$currentX = null;
		$currentZ = null;
		$map = [];
		$reference = [];
		$blockSources = [];
		$this->getCurrentSelection()->useOnBlocks(function (int $x, int $y, int $z) use (&$currentX, &$currentZ, &$map, &$reference, $handler): void {
			if ($currentX !== $x || $currentZ !== $z) {
				//Prepare data sets for all y-values
				$currentX = $x;
				$currentZ = $z;
				$map = HeightMapCache::generateFullDepthMap($x, $z);
				$reference = $map;
				$neighbors = [];
				for ($i = -1; $i <= 1; $i++) {
					for ($j = -1; $j <= 1; $j++) {
						if ($i === 0 && $j === 0) {
							continue;
						}
						foreach (HeightMapCache::generateFullDepthMap($x + $i, $z + $j) as $height => $depth) {
							$reference[$height] += $depth;
						}
					}
				}
				foreach ($reference as $height => $depth) {
					$reference[$height] = (int) round($depth / 9);
				}
			}
			if ($map[$y] === $reference[$y]) {
				return;
			}
			if ($reference[$y] === 0) {
				$handler->changeBlock($x, $y, $z, 0);
				return;
			}
			if ($reference[$y] < $reference[$y + 1]) { //get blocks from top
				$handler->changeBlock($x, $y, $z, 1 << 4); //TODO
			} else { //get blocks from bottom
				$handler->changeBlock($x, $y, $z, 1 << 4); //TODO
			}
		}, SelectionContext::full(), $this->getTotalSelection());
	}
}