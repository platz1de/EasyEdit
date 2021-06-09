<?php

namespace platz1de\EasyEdit\task\selection;

use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\MovingCube;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\task\EditTask;
use platz1de\EasyEdit\task\queued\QueuedEditTask;
use platz1de\EasyEdit\task\selection\cubic\CubicStaticUndo;
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

			$my = min(Level::Y_MASK, max(0, $y + $direction->getY()));
			$iterator->moveTo($x + $direction->getX(), $my, $z + $direction->getZ());
			$toUndo->addBlock($x + $direction->getX(), $my, $z + $direction->getZ(), $iterator->currentSubChunk->getBlockId($x + $direction->getX() & 0x0f, $my & 0x0f, $z + $direction->getZ() & 0x0f), $iterator->currentSubChunk->getBlockData($x + $direction->getX() & 0x0f, $my & 0x0f, $z + $direction->getZ() & 0x0f), false);
			$iterator->currentSubChunk->setBlock(($x + $direction->getX()) & 0x0f, $my & 0x0f, ($z + $direction->getZ()) & 0x0f, $id, $data);
			$changed++;

			if (isset($tiles[Level::blockHash($x + $direction->getX(), $my, $z + $direction->getZ())])) {
				$toUndo->addTile($tiles[Level::blockHash($x + $direction->getX(), $my, $z + $direction->getZ())]);
				unset($tiles[Level::blockHash($x + $direction->getX(), $my, $z + $direction->getZ())]);
			}
			if (isset($tiles[Level::blockHash($x, $y, $z)])) {
				$toUndo->addTile($tiles[Level::blockHash($x, $y, $z)]);
				$tiles[Level::blockHash($x + $direction->getX(), $my, $z + $direction->getZ())] = TileUtils::offsetCompound($tiles[Level::blockHash($x, $y, $z)], $direction);
				unset($tiles[Level::blockHash($x, $y, $z)]);
			}
		});
	}

	/**
	 * @param Selection             $selection
	 * @param float                 $time
	 * @param int                   $changed
	 * @param AdditionalDataManager $data
	 */
	public function notifyUser(Selection $selection, float $time, int $changed, AdditionalDataManager $data): void
	{
		Messages::send($selection->getPlayer(), "blocks-set", ["{time}" => $time, "{changed}" => $changed]);
	}
}