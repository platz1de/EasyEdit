<?php

namespace platz1de\EasyEdit\task\selection;

use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\MovingCube;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\task\EditTask;
use platz1de\EasyEdit\task\EditTaskHandler;
use platz1de\EasyEdit\task\queued\QueuedEditTask;
use platz1de\EasyEdit\task\selection\cubic\CubicStaticUndo;
use platz1de\EasyEdit\task\selection\type\SettingNotifier;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\worker\WorkerAdapter;
use pocketmine\math\Vector3;
use pocketmine\world\Position;

class MoveTask extends EditTask
{
	use CubicStaticUndo;
	use SettingNotifier;

	/**
	 * @param MovingCube $selection
	 * @param Position   $place
	 */
	public static function queue(MovingCube $selection, Position $place): void
	{
		WorkerAdapter::queue(new QueuedEditTask($selection, new Pattern([]), $place, self::class, new AdditionalDataManager(["edit" => true]), new Vector3(0, 0, 0)));
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "move";
	}

	/**
	 * @param EditTaskHandler       $handler
	 * @param Selection             $selection
	 * @param Vector3               $place
	 * @param AdditionalDataManager $data
	 */
	public function execute(EditTaskHandler $handler, Selection $selection, Vector3 $place, AdditionalDataManager $data): void
	{
		/** @var MovingCube $s */
		$s = $selection;
		$direction = $s->getDirection();
		$selection->useOnBlocks($place, function (int $x, int $y, int $z) use ($handler, $direction): void {
			$handler->changeBlock($x, $y, $z, 0);
			$handler->copyBlock($x + $direction->getFloorX(), $y + $direction->getY(), $z + $direction->getFloorZ(), $x, $y, $z);
		}, SelectionContext::full());
	}
}