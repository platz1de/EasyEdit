<?php

namespace platz1de\EasyEdit\task\selection;

use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\selection\StackedCube;
use platz1de\EasyEdit\task\EditTask;
use platz1de\EasyEdit\task\EditTaskHandler;
use platz1de\EasyEdit\task\queued\QueuedEditTask;
use platz1de\EasyEdit\task\selection\cubic\CubicStaticUndo;
use platz1de\EasyEdit\task\selection\type\SettingNotifier;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\worker\WorkerAdapter;
use pocketmine\math\Vector3;
use pocketmine\world\Position;

class StackTask extends EditTask
{
	use CubicStaticUndo;
	use SettingNotifier;

	/**
	 * @param StackedCube $selection
	 * @param Position    $place
	 */
	public static function queue(StackedCube $selection, Position $place): void
	{
		WorkerAdapter::queue(new QueuedEditTask($selection, new Pattern([]), $place, self::class, new AdditionalDataManager(["edit" => true]), new Vector3(0, 0, 0)));
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "stack";
	}

	/**
	 * @param EditTaskHandler       $handler
	 * @param Selection             $selection
	 * @param Vector3               $place
	 * @param AdditionalDataManager $data
	 */
	public function execute(EditTaskHandler $handler, Selection $selection, Vector3 $place, AdditionalDataManager $data): void
	{
		/** @var StackedCube $selection */
		$originalSize = $selection->getPos2()->subtractVector($selection->getPos1())->add(1, 1, 1);
		$start = $selection->getDirection()->getX() < 0 || $selection->getDirection()->getY() < 0 || $selection->getDirection()->getZ() < 0 ? $selection->getPos2() : $selection->getPos1();
		$selection->useOnBlocks($place, function (int $x, int $y, int $z) use ($handler, $originalSize, $start): void {
			$handler->copyBlock($x, $y, $z, $start->getFloorX() + ($x - $start->getX()) % $originalSize->getX(), $start->getFloorY() + ($y - $start->getY()) % $originalSize->getY(), $start->getFloorZ() + ($z - $start->getZ()) % $originalSize->getZ());
		}, SelectionContext::full());
	}
}