<?php

namespace platz1de\EasyEdit\task\editing;

use platz1de\EasyEdit\convert\BlockStateConvertor;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\ExpandingStaticBlockListSelection;
use platz1de\EasyEdit\task\editing\type\SettingNotifier;
use platz1de\EasyEdit\thread\ChunkCollector;
use platz1de\EasyEdit\thread\input\ChunkInputData;
use platz1de\EasyEdit\thread\input\TaskInputData;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class PasteBlockStatesTask extends EditTask
{
	use SettingNotifier;

	private float $progress = 0;

	/**
	 * @param string                $owner
	 * @param string                $world
	 * @param AdditionalDataManager $data
	 * @param Vector3               $start
	 * @return PasteBlockStatesTask
	 */
	public static function from(string $owner, string $world, AdditionalDataManager $data, Vector3 $start): PasteBlockStatesTask
	{
		return new self($owner, $world, $data, $start);
	}

	/**
	 * @param string  $player
	 * @param string  $world
	 * @param Vector3 $start
	 */
	public static function queue(string $player, string $world, Vector3 $start): void
	{
		TaskInputData::fromTask(self::from($player, $world, new AdditionalDataManager(true, true), $start));
	}

	public function execute(): void
	{
		$this->getDataManager()->useFastSet();
		$this->getDataManager()->setFinal();
		ChunkCollector::init($this->getWorld());
		ChunkCollector::collectInput(ChunkInputData::empty());
		$this->run();
		ChunkCollector::clear();
	}

	public function executeEdit(EditTaskHandler $handler): void
	{
		$states = BlockStateConvertor::getAllKnownStates();
		$count = count($states);
		$loadedChunks = [];
		$x = $this->getPosition()->getFloorX();
		$y = $this->getPosition()->getFloorY();
		$z = $this->getPosition()->getFloorZ();
		$i = 0;
		foreach ($states as $id => $state) {
			$chunk = World::chunkHash(($x + floor($i / 100) * 2) >> 4, ($z + ($i % 100) * 2) >> 4);
			if (!isset($loadedChunks[$chunk])) {
				$loadedChunks[$chunk] = true;
				$this->progress = $i / $count;
				if (!$this->requestRuntimeChunks($handler, [$chunk])) {
					return;
				}
			}
			$handler->changeBlock((int) ($x + floor($i / 100) * 2), $y, $z + ($i % 100) * 2, $id);
			$i++;
		}
	}

	public function getUndoBlockList(): BlockListSelection
	{
		return new ExpandingStaticBlockListSelection($this->getOwner(), $this->getWorld(), $this->getPosition());
	}

	public function getTaskName(): string
	{
		return "fill";
	}

	public function getProgress(): float
	{
		return $this->progress;
	}
}