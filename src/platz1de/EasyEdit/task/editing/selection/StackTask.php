<?php

namespace platz1de\EasyEdit\task\editing\selection;

use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\selection\StackedCube;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\selection\cubic\CubicStaticUndo;
use platz1de\EasyEdit\task\editing\type\SettingNotifier;
use platz1de\EasyEdit\thread\input\TaskInputData;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use pocketmine\math\Vector3;
use pocketmine\world\Position;

class StackTask extends SelectionEditTask
{
	use CubicStaticUndo;
	use SettingNotifier;

	/**
	 * @var StackedCube
	 */
	protected Selection $selection;

	/**
	 * @param string                $owner
	 * @param string                $world
	 * @param AdditionalDataManager $data
	 * @param Selection             $selection
	 * @param Vector3               $position
	 * @param Vector3               $splitOffset
	 * @return StackTask
	 */
	public static function from(string $owner, string $world, AdditionalDataManager $data, Selection $selection, Vector3 $position, Vector3 $splitOffset): StackTask
	{
		$instance = new self($owner);
		SelectionEditTask::initSelection($instance, $world, $data, $selection, $position, $splitOffset);
		return $instance;
	}

	/**
	 * @param StackedCube $selection
	 * @param Position    $place
	 */
	public static function queue(StackedCube $selection, Position $place): void
	{
		TaskInputData::fromTask(self::from($selection->getPlayer(), $place->getWorld()->getFolderName(), new AdditionalDataManager(true, true), $selection, $place->asVector3(), new Vector3(0, 0, 0)));
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "stack";
	}

	public function executeEdit(EditTaskHandler $handler): void
	{
		$selection = $this->selection;
		$originalSize = $selection->getPos2()->subtractVector($selection->getPos1())->add(1, 1, 1);
		$start = $selection->getDirection()->getX() < 0 || $selection->getDirection()->getY() < 0 || $selection->getDirection()->getZ() < 0 ? $selection->getPos2() : $selection->getPos1();
		$selection->useOnBlocks($this->getPosition(), function (int $x, int $y, int $z) use ($handler, $originalSize, $start): void {
			$handler->copyBlock($x, $y, $z, $start->getFloorX() + ($x - $start->getX()) % $originalSize->getX(), $start->getFloorY() + ($y - $start->getY()) % $originalSize->getY(), $start->getFloorZ() + ($z - $start->getZ()) % $originalSize->getZ());
		}, SelectionContext::full(), $this->getTotalSelection());
	}
}