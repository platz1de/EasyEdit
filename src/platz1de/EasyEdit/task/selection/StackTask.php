<?php

namespace platz1de\EasyEdit\task\selection;

use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\StackedCube;
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
		/** @var StackedCube $selection */
		$originalSize = $selection->getPos2()->subtractVector($selection->getPos1())->add(1, 1, 1);
		$start = $selection->getDirection()->getX() < 0 || $selection->getDirection()->getY() < 0 || $selection->getDirection()->getZ() < 0 ? $selection->getPos2() : $selection->getPos1();
		$selection->useOnBlocks($place, function (int $x, int $y, int $z) use ($iterator, &$tiles, $toUndo, &$changed, $originalSize, $start): void {
			$originalX = $start->getFloorX() + ($x - $start->getX()) % $originalSize->getX();
			$originalY = $start->getFloorY() + ($y - $start->getY()) % $originalSize->getY();
			$originalZ = $start->getFloorZ() + ($z - $start->getZ()) % $originalSize->getZ();

			$id = $iterator->getBlockAt($originalX, $originalY, $originalZ);

			$toUndo->addBlock($x, $y, $z, $iterator->getBlockAt($x, $y, $z), false);
			$iterator->getCurrent()->setFullBlock($x & 0x0f, $y & 0x0f, $z & 0x0f, $id);
			$changed++;

			if (isset($tiles[World::blockHash($x, $y, $z)])) {
				$toUndo->addTile($tiles[World::blockHash($x, $y, $z)]);
				unset($tiles[World::blockHash($x, $y, $z)]);
			}
			if (isset($tiles[World::blockHash($originalX, $originalY, $originalZ)])) {
				$tiles[World::blockHash($x, $y, $z)] = TileUtils::offsetCompound($tiles[World::blockHash($originalX, $originalY, $originalZ)], new Vector3($x - $originalX, $y - $originalY, $z - $originalZ));
			}
		});
	}
}