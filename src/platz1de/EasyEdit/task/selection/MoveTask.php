<?php

namespace platz1de\EasyEdit\task\selection;

use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\MovingCube;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\task\EditTask;
use platz1de\EasyEdit\task\queued\QueuedEditTask;
use platz1de\EasyEdit\task\selection\cubic\CubicStaticUndo;
use platz1de\EasyEdit\task\selection\type\SettingNotifier;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\TileUtils;
use platz1de\EasyEdit\worker\WorkerAdapter;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\utils\SubChunkIteratorManager;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;

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
		WorkerAdapter::queue(new QueuedEditTask($selection, new Pattern([], []), $place, self::class, new AdditionalDataManager(["edit" => true])));
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "move";
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
		/** @var MovingCube $s */
		$s = $selection;
		$direction = $s->getDirection();
		$selection->useOnBlocks($place, function (int $x, int $y, int $z) use ($iterator, &$tiles, $toUndo, &$changed, $direction): void {
			$iterator->moveTo($x, $y, $z);

			$id = $iterator->currentSubChunk->getBlockId($x & 0x0f, $y & 0x0f, $z & 0x0f);
			$data = $iterator->currentSubChunk->getBlockData($x & 0x0f, $y & 0x0f, $z & 0x0f);

			$toUndo->addBlock($x, $y, $z, $id, $data);
			$iterator->currentSubChunk->setBlock($x & 0x0f, $y & 0x0f, $z & 0x0f, 0, 0);

			$newX = $x + $direction->getFloorX();
			$newY = (int) min(Level::Y_MASK, max(0, $y + $direction->getY()));
			$newZ = $z + $direction->getFloorZ();

			$iterator->moveTo($newX, $newY, $newZ);
			$toUndo->addBlock($newX, $newY, $newZ, $iterator->currentSubChunk->getBlockId($newX & 0x0f, $newY & 0x0f, $newZ & 0x0f), $iterator->currentSubChunk->getBlockData($newX & 0x0f, $newY & 0x0f, $newZ & 0x0f), false);
			$iterator->currentSubChunk->setBlock($newX & 0x0f, $newY & 0x0f, $newZ & 0x0f, $id, $data);
			$changed++;

			if (isset($tiles[Level::blockHash($newX, $newY, $newZ)])) {
				$toUndo->addTile($tiles[Level::blockHash($newX, $newY, $newZ)]);
				unset($tiles[Level::blockHash($newX, $newY, $newZ)]);
			}
			if (isset($tiles[Level::blockHash($x, $y, $z)])) {
				$toUndo->addTile($tiles[Level::blockHash($x, $y, $z)]);
				$tiles[Level::blockHash($newX, $newY, $newZ)] = TileUtils::offsetCompound($tiles[Level::blockHash($x, $y, $z)], $direction);
				unset($tiles[Level::blockHash($x, $y, $z)]);
			}
		});
	}
}