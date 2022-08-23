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
	 * @param MovingCube $selection
	 */
	public function __construct(MovingCube $selection)
	{
		parent::__construct($selection);
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "move";
	}

	public function executeEdit(EditTaskHandler $handler, Vector3 $min, Vector3 $max): void
	{
		$selection = $this->current;
		$direction = $selection->getDirection();
		$handler->getChanges()->checkCachedData();
		$selection->useOnBlocks(function (int $x, int $y, int $z) use ($handler, $direction): void {
			$handler->changeBlock($x, $y, $z, 0);
			$handler->copyBlock($x + $direction->getFloorX(), $y + $direction->getFloorY(), $z + $direction->getFloorZ(), $x, $y, $z, false);
		}, SelectionContext::full(), $this->getTotalSelection(), $min, $max);
	}
}