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
use platz1de\EasyEdit\utils\SafeSubChunkExplorer;
use platz1de\EasyEdit\utils\TileUtils;
use platz1de\EasyEdit\worker\WorkerAdapter;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\Position;
use pocketmine\world\World;

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
	 * @param SafeSubChunkExplorer  $iterator
	 * @param CompoundTag[]         $tiles
	 * @param Selection             $selection
	 * @param Pattern               $pattern
	 * @param Vector3               $place
	 * @param BlockListSelection    $toUndo
	 * @param SafeSubChunkExplorer  $origin
	 * @param int                   $changed
	 * @param AdditionalDataManager $data
	 */
	public function execute(SafeSubChunkExplorer $iterator, array &$tiles, Selection $selection, Pattern $pattern, Vector3 $place, BlockListSelection $toUndo, SafeSubChunkExplorer $origin, int &$changed, AdditionalDataManager $data): void
	{
		/** @var MovingCube $s */
		$s = $selection;
		$direction = $s->getDirection();
		$selection->useOnBlocks($place, function (int $x, int $y, int $z) use ($iterator, &$tiles, $toUndo, &$changed, $direction): void {
			$id = $iterator->getBlockAt($x, $y, $z);

			$toUndo->addBlock($x, $y, $z, $id);
			$iterator->setBlockAt($x, $y, $z, 0);

			$newX = $x + $direction->getFloorX();
			$newY = (int) min(World::Y_MAX - 1, max(0, $y + $direction->getY()));
			$newZ = $z + $direction->getFloorZ();

			$toUndo->addBlock($newX, $newY, $newZ, $iterator->getBlockAt($newX, $newY, $newZ), false);
			$iterator->setBlockAt($newX, $newY, $newZ, $id);
			$changed++;

			if (isset($tiles[World::blockHash($newX, $newY, $newZ)])) {
				$toUndo->addTile($tiles[World::blockHash($newX, $newY, $newZ)]);
				unset($tiles[World::blockHash($newX, $newY, $newZ)]);
			}
			if (isset($tiles[World::blockHash($x, $y, $z)])) {
				$toUndo->addTile($tiles[World::blockHash($x, $y, $z)]);
				$tiles[World::blockHash($newX, $newY, $newZ)] = TileUtils::offsetCompound($tiles[World::blockHash($x, $y, $z)], $direction);
				unset($tiles[World::blockHash($x, $y, $z)]);
			}
		});
	}
}