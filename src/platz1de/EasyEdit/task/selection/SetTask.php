<?php

namespace platz1de\EasyEdit\task\selection;

use Closure;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\task\EditTask;
use platz1de\EasyEdit\task\EditTaskHandler;
use platz1de\EasyEdit\task\queued\QueuedEditTask;
use platz1de\EasyEdit\task\selection\cubic\CubicStaticUndo;
use platz1de\EasyEdit\task\selection\type\SettingNotifier;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\worker\WorkerAdapter;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\world\Position;

class SetTask extends EditTask
{
	use CubicStaticUndo;
	use SettingNotifier;

	/**
	 * @param Selection    $selection
	 * @param Pattern      $pattern
	 * @param Position     $place
	 * @param Closure|null $finish
	 */
	public static function queue(Selection $selection, Pattern $pattern, Position $place, ?Closure $finish = null): void
	{
		WorkerAdapter::queue(new QueuedEditTask($selection, $pattern, $place, self::class, new AdditionalDataManager(["edit" => true]), new Vector3(0, 0, 0), $finish));
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "set";
	}

	/**
	 * @param EditTaskHandler       $handler
	 * @param Selection             $selection
	 * @param Pattern               $pattern
	 * @param Vector3               $place
	 * @param AdditionalDataManager $data
	 */
	public function execute(EditTaskHandler $handler, Selection $selection, Pattern $pattern, Vector3 $place, AdditionalDataManager $data): void
	{
		$selection->useOnBlocks($place, function (int $x, int $y, int $z) use ($handler, $pattern, $selection): void {
			$block = $pattern->getFor($x, $y, $z, $handler->getOrigin(), $selection);
			if ($block instanceof Block) {
				$handler->changeBlock($x, $y, $z, $block->getFullId());
			}
		});
	}
}