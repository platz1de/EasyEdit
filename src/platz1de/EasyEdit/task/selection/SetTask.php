<?php

namespace platz1de\EasyEdit\task\selection;

use Closure;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\StaticBlockListSelection;
use platz1de\EasyEdit\task\EditTask;
use platz1de\EasyEdit\task\QueuedTask;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\worker\WorkerAdapter;
use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\utils\SubChunkIteratorManager;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;

class SetTask extends EditTask
{
	/**
	 * @param Selection    $selection
	 * @param Pattern      $pattern
	 * @param Position     $place
	 * @param Closure|null $finish
	 */
	public static function queue(Selection $selection, Pattern $pattern, Position $place, ?Closure $finish = null): void
	{
		WorkerAdapter::queue(new QueuedTask($selection, $pattern, $place, self::class, new AdditionalDataManager(), $finish));
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "set";
	}

	/**
	 * @param SubChunkIteratorManager $iterator
	 * @param CompoundTag[]           $tiles
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
		$selection->useOnBlocks($place, function (int $x, int $y, int $z) use ($iterator, &$tiles, $pattern, $toUndo, $origin, &$changed, $selection): void {
			$b = $pattern->getFor($x, $y, $z, $origin, $selection);
			if ($b instanceof Block) {
				$iterator->moveTo($x, $y, $z);
				$toUndo->addBlock($x, $y, $z, $iterator->currentSubChunk->getBlockId($x & 0x0f, $y & 0x0f, $z & 0x0f), $iterator->currentSubChunk->getBlockData($x & 0x0f, $y & 0x0f, $z & 0x0f));
				$iterator->currentSubChunk->setBlock($x & 0x0f, $y & 0x0f, $z & 0x0f, $b->getId(), $b->getDamage());
				$changed++;

				if (isset($tiles[Level::blockHash($x, $y, $z)])) {
					$toUndo->addTile($tiles[Level::blockHash($x, $y, $z)]);
					unset($tiles[Level::blockHash($x, $y, $z)]);
				}
			}
		});
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
		$size = $selection->getRealSize();
		return new StaticBlockListSelection($selection->getPlayer(), $level, $selection->getCubicStart(), $size->getX(), $size->getY(), $size->getZ());
	}

	/**
	 * @param Selection $selection
	 * @param float     $time
	 * @param int       $changed
	 */
	public function notifyUser(Selection $selection, float $time, int $changed): void
	{
		Messages::send($selection->getPlayer(), "blocks-set", ["{time}" => $time, "{changed}" => $changed]);
	}
}