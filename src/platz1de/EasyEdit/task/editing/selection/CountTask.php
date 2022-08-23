<?php

namespace platz1de\EasyEdit\task\editing\selection;

use platz1de\EasyEdit\convert\BlockStateConvertor;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\NonSavingBlockListSelection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\selection\StaticBlockListSelection;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\thread\output\session\MessageSendData;
use platz1de\EasyEdit\utils\MixedUtils;
use pocketmine\math\Vector3;

class CountTask extends SelectionEditTask
{
	/**
	 * @var int[]
	 */
	private array $counted = [];

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "count";
	}

	/**
	 * @return StaticBlockListSelection
	 */
	public function getUndoBlockList(): BlockListSelection
	{
		return new NonSavingBlockListSelection();
	}

	/**
	 * @param string $time
	 * @param string $changed
	 */
	public function notifyUser(string $time, string $changed): void
	{
		arsort($this->counted);
		$blocks = [];
		foreach ($this->counted as $block => $count) {
			$blocks[] = BlockStateConvertor::getState($block) . ": " . MixedUtils::humanReadable($count);
		}
		$this->sendOutputPacket(new MessageSendData("blocks-counted", ["{time}" => $time, "{changed}" => (string) array_sum($this->counted), "{blocks}" => implode("\n", $blocks)]));
	}

	public function executeEdit(EditTaskHandler $handler, Vector3 $min, Vector3 $max): void
	{
		$this->selection->useOnBlocks(function (int $x, int $y, int $z) use ($handler): void {
			$id = $handler->getBlock($x, $y, $z);
			if (isset($this->counted[$id])) {
				$this->counted[$id]++;
			} else {
				$this->counted[$id] = 1;
			}
		}, SelectionContext::full(), $min, $max);
	}
}