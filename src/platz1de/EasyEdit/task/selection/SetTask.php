<?php

namespace platz1de\EasyEdit\task\selection;

use Closure;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\task\EditTask;
use platz1de\EasyEdit\task\queued\QueuedEditTask;
use platz1de\EasyEdit\task\selection\cubic\CubicStaticUndo;
use platz1de\EasyEdit\task\selection\type\SettingNotifier;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\SafeSubChunkIteratorManager;
use platz1de\EasyEdit\worker\WorkerAdapter;
use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;

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
		WorkerAdapter::queue(new QueuedEditTask($selection, $pattern, $place, self::class, new AdditionalDataManager(["edit" => true]), new Vector3(), $finish));
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "set";
	}

	/**
	 * @param SafeSubChunkIteratorManager $iterator
	 * @param CompoundTag[]               $tiles
	 * @param Selection                   $selection
	 * @param Pattern                     $pattern
	 * @param Vector3                     $place
	 * @param BlockListSelection          $toUndo
	 * @param SafeSubChunkIteratorManager $origin
	 * @param int                         $changed
	 * @param AdditionalDataManager       $data
	 */
	public function execute(SafeSubChunkIteratorManager $iterator, array &$tiles, Selection $selection, Pattern $pattern, Vector3 $place, BlockListSelection $toUndo, SafeSubChunkIteratorManager $origin, int &$changed, AdditionalDataManager $data): void
	{
		$selection->useOnBlocks($place, function (int $x, int $y, int $z) use ($iterator, &$tiles, $pattern, $toUndo, $origin, &$changed, $selection): void {
			$b = $pattern->getFor($x, $y, $z, $origin, $selection);
			if ($b instanceof Block) {
				$iterator->moveTo($x, $y, $z);
				$toUndo->addBlock($x, $y, $z, $iterator->getCurrent()->getBlockId($x & 0x0f, $y & 0x0f, $z & 0x0f), $iterator->getCurrent()->getBlockData($x & 0x0f, $y & 0x0f, $z & 0x0f));
				$iterator->getCurrent()->setBlock($x & 0x0f, $y & 0x0f, $z & 0x0f, $b->getId(), $b->getDamage());
				$changed++;

				if (isset($tiles[Level::blockHash($x, $y, $z)])) {
					$toUndo->addTile($tiles[Level::blockHash($x, $y, $z)]);
					unset($tiles[Level::blockHash($x, $y, $z)]);
				}
			}
		});
	}
}