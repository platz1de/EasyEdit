<?php

namespace platz1de\EasyEdit\task\selection;

use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\StaticBlockListSelection;
use platz1de\EasyEdit\task\EditTask;
use platz1de\EasyEdit\task\EditTaskResult;
use platz1de\EasyEdit\task\QueuedTask;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\MixedUtils;
use platz1de\EasyEdit\worker\WorkerAdapter;
use pocketmine\block\BlockFactory;
use pocketmine\level\Position;
use pocketmine\level\utils\SubChunkIteratorManager;
use pocketmine\math\Vector3;

class CountTask extends EditTask
{
	/**
	 * @param Selection $selection
	 * @param Position  $place
	 */
	public static function queue(Selection $selection, Position $place): void
	{
		WorkerAdapter::queue(new QueuedTask($selection, new Pattern([], []), $place, self::class, new AdditionalDataManager(), static function (EditTaskResult $result) {
			//Nothing is edited
		}));
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "count";
	}

	/**
	 * @param SubChunkIteratorManager $iterator
	 * @param array                   $tiles
	 * @param Selection               $selection
	 * @param Pattern                 $pattern
	 * @param Vector3                 $place
	 * @param BlockListSelection      $toUndo
	 * @param SubChunkIteratorManager $origin
	 * @param int                     $changed
	 * @param AdditionalDataManager   $data
	 */
	public function execute(SubChunkIteratorManager $iterator, array &$tiles, Selection $selection, Pattern $pattern, Vector3 $place, BlockListSelection $toUndo, SubChunkIteratorManager $origin, int &$changed, AdditionalDataManager $data): void
	{
		$blocks = $data->getDataKeyed("blocks", []);
		$selection->useOnBlocks($place, function (int $x, int $y, int $z) use ($iterator, &$blocks, &$changed): void {
			$iterator->moveTo($x, $y, $z);
			$id = $iterator->currentSubChunk->getBlockId($x & 0x0f, $y & 0x0f, $z & 0x0f);
			if (isset($blocks[$id])) {
				$blocks[$id]++;
			} else {
				$blocks[$id] = 1;
			}
			$changed++;
		});
		arsort($blocks, SORT_NUMERIC);
		$data->setDataKeyed("blocks", $blocks);
	}

	/**
	 * @param Selection             $selection
	 * @param Vector3               $place
	 * @param string                $level
	 * @param AdditionalDataManager $data
	 * @return StaticBlockListSelection
	 */
	public function getUndoBlockList(Selection $selection, Vector3 $place, string $level, AdditionalDataManager $data): BlockListSelection
	{
		//TODO: make this optional
		return new StaticBlockListSelection($selection->getPlayer(), "", new Vector3(0, 0, 0), new Vector3(0, 0, 0));
	}

	/**
	 * @param Selection             $selection
	 * @param float                 $time
	 * @param int                   $changed
	 * @param AdditionalDataManager $data
	 */
	public function notifyUser(Selection $selection, float $time, int $changed, AdditionalDataManager $data): void
	{
		Messages::send($selection->getPlayer(), "blocks-counted", ["{time}" => $time, "{changed}" => $changed]);
		$msg = "";
		foreach ($data->getDataKeyed("blocks") as $block => $count) {
			$msg .= BlockFactory::get($block)->getName() . ": " . MixedUtils::humanReadable($count) . "\n";
		}
		Messages::send($selection->getPlayer(), $msg, [], false, false);
	}
}