<?php

namespace platz1de\EasyEdit\task\editing\selection;

use platz1de\EasyEdit\selection\MovingCube;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\selection\cubic\CubicStaticUndo;
use platz1de\EasyEdit\task\editing\type\SettingNotifier;
use pocketmine\math\Vector3;

class MoveTask extends SelectionEditTask
{
	use CubicStaticUndo;
	use SettingNotifier;

	/**
	 * @var MovingCube
	 */
	protected Selection $current;

	/**
	 * @param string    $world
	 * @param Selection $selection
	 * @param Vector3   $position
	 * @return MoveTask
	 */
	public static function from(string $world, Selection $selection, Vector3 $position): MoveTask
	{
		$instance = new self($world, $position);
		$instance->selection = $selection;
		return $instance;
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "move";
	}

	public function executeEdit(EditTaskHandler $handler): void
	{
		$selection = $this->current;
		$direction = $selection->getDirection();
		$handler->getChanges()->checkCachedData();
		$selection->useOnBlocks(function (int $x, int $y, int $z) use ($handler, $direction): void {
			$handler->changeBlock($x, $y, $z, 0);
			$handler->copyBlock($x + $direction->getFloorX(), $y + $direction->getFloorY(), $z + $direction->getFloorZ(), $x, $y, $z, false);
		}, SelectionContext::full(), $this->getTotalSelection());
	}
}