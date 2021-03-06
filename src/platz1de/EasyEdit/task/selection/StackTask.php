<?php

namespace platz1de\EasyEdit\task\selection;

use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\StackedCube;
use platz1de\EasyEdit\selection\StaticBlockListSelection;
use platz1de\EasyEdit\task\EditTask;
use platz1de\EasyEdit\task\QueuedTask;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\TileUtils;
use platz1de\EasyEdit\worker\WorkerAdapter;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\utils\SubChunkIteratorManager;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;

class StackTask extends EditTask
{
	/**
	 * @param StackedCube $selection
	 * @param Position    $place
	 */
	public static function queue(StackedCube $selection, Position $place): void
	{
		WorkerAdapter::queue(new QueuedTask($selection, new Pattern([], []), $place, self::class, new AdditionalDataManager()));
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "stack";
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
		$originalSize = $selection->getPos2()->subtract($selection->getPos1())->add(1, 1, 1);
		$start = $selection->getPos1();
		$selection->useOnBlocks($place, function (int $x, int $y, int $z) use ($iterator, &$tiles, $toUndo, &$changed, $originalSize, $start): void {
			$originalX = $start->getX() + abs(($x - $start->getX()) % $originalSize->getX());
			$originalY = $start->getY() + abs(($y - $start->getY()) % $originalSize->getY());
			$originalZ = $start->getZ() + abs(($z - $start->getZ()) % $originalSize->getZ());

			$iterator->moveTo($originalX, $originalY, $originalZ);

			$id = $iterator->currentSubChunk->getBlockId($originalX & 0x0f, $originalY & 0x0f, $originalZ & 0x0f);
			$data = $iterator->currentSubChunk->getBlockData($originalX & 0x0f, $originalY & 0x0f, $originalZ & 0x0f);

			$iterator->moveTo($x, $y, $z);
			$toUndo->addBlock($x, $y, $z, $iterator->currentSubChunk->getBlockId($x & 0x0f, $y & 0x0f, $z & 0x0f), $iterator->currentSubChunk->getBlockData($x & 0x0f, $y & 0x0f, $z & 0x0f), false);
			$iterator->currentSubChunk->setBlock($x & 0x0f, $y & 0x0f, $z & 0x0f, $id, $data);
			$changed++;

			if (isset($tiles[Level::blockHash($x, $y, $z)])) {
				$toUndo->addTile($tiles[Level::blockHash($x, $y, $z)]);
				unset($tiles[Level::blockHash($x, $y, $z)]);
			}
			if (isset($tiles[Level::blockHash($originalX, $originalY, $originalZ)])) {
				$tiles[Level::blockHash($x, $y, $z)] = TileUtils::offsetCompound($tiles[Level::blockHash($originalX, $originalY, $originalZ)], new Vector3($x - $originalX, $y - $originalY, $z - $originalZ));
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