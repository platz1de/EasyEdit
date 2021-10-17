<?php

namespace platz1de\EasyEdit\task\selection;

use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\selection\StaticBlockListSelection;
use platz1de\EasyEdit\task\EditTask;
use platz1de\EasyEdit\task\EditTaskHandler;
use platz1de\EasyEdit\task\queued\QueuedEditTask;
use platz1de\EasyEdit\thread\EditAdapter;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\MixedUtils;
use pocketmine\block\BlockFactory;
use pocketmine\math\Vector3;
use pocketmine\world\Position;
use pocketmine\world\World;

class CountTask extends EditTask
{
	/**
	 * @param Selection $selection
	 * @param Position  $place
	 */
	public static function queue(Selection $selection, Position $place): void
	{
		EditAdapter::queue(new QueuedEditTask($selection, new Pattern([]), $place->getWorld()->getFolderName(), $place->asVector3(), self::class, new AdditionalDataManager(false, false), new Vector3(0, 0, 0)), null);
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "count";
	}

	/**
	 * @param EditTaskHandler       $handler
	 * @param Selection             $selection
	 * @param Vector3               $place
	 * @param AdditionalDataManager $data
	 */
	public function execute(EditTaskHandler $handler, Selection $selection, Vector3 $place, AdditionalDataManager $data): void
	{
		$blocks = $data->getCountedBlocks();
		$selection->useOnBlocks($place, function (int $x, int $y, int $z) use ($handler, &$blocks): void {
			$id = $handler->getBlock($x, $y, $z);
			if (isset($blocks[$id])) {
				$blocks[$id]++;
			} else {
				$blocks[$id] = 1;
			}
		}, SelectionContext::full());
		arsort($blocks, SORT_NUMERIC);
		$data->setCountedBlocks($blocks);
	}

	/**
	 * @param Selection             $selection
	 * @param Vector3               $place
	 * @param string                $world
	 * @param AdditionalDataManager $data
	 * @return StaticBlockListSelection
	 */
	public function getUndoBlockList(Selection $selection, Vector3 $place, string $world, AdditionalDataManager $data): BlockListSelection
	{
		//TODO: make this optional
		return new StaticBlockListSelection($selection->getPlayer(), "", new Vector3(0, World::Y_MIN, 0), new Vector3(0, World::Y_MIN, 0));
	}

	/**
	 * @param string                $player
	 * @param float                 $time
	 * @param string                $changed
	 * @param AdditionalDataManager $data
	 */
	public static function notifyUser(string $player, float $time, string $changed, AdditionalDataManager $data): void
	{
		Messages::send($player, "blocks-counted", ["{time}" => (string) $time, "{changed}" => array_sum($data->getCountedBlocks())]);
		$msg = "";
		foreach ($data->getCountedBlocks() as $block => $count) {
			$msg .= BlockFactory::getInstance()->fromFullBlock($block)->getName() . ": " . MixedUtils::humanReadable($count) . "\n";
		}
		Messages::send($player, $msg, [], false, false);
	}
}