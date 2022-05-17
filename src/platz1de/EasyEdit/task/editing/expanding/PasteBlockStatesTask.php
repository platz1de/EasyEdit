<?php

namespace platz1de\EasyEdit\task\editing\expanding;

use platz1de\EasyEdit\convert\BlockStateConvertor;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\type\SettingNotifier;
use platz1de\EasyEdit\thread\input\TaskInputData;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class PasteBlockStatesTask extends ExpandingTask
{
	use SettingNotifier;

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

	public function executeEdit(EditTaskHandler $handler): void
	{
		$states = BlockStateConvertor::getAllKnownStates();
		$count = count($states);
		$x = $this->getPosition()->getFloorX();
		$y = $this->getPosition()->getFloorY();
		$z = $this->getPosition()->getFloorZ();

		if (!$this->checkRuntimeChunk($handler, World::chunkHash($x, $z), 0, 1)) {
			return;
		}

		$i = 0;
		foreach ($states as $id => $state) {
			$chunk = World::chunkHash(($x + floor($i / 100) * 2) >> 4, ($z + ($i % 100) * 2) >> 4);
			if (!$this->checkRuntimeChunk($handler, $chunk, $i, $count)) {
				return;
			}
			$handler->changeBlock((int) ($x + floor($i / 100) * 2), $y, $z + ($i % 100) * 2, $id);
			$i++;
		}
	}

	public function getTaskName(): string
	{
		return "fill";
	}
}