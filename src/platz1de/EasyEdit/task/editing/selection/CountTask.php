<?php

namespace platz1de\EasyEdit\task\editing\selection;

use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\selection\StaticBlockListSelection;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\thread\output\session\MessageSendData;
use platz1de\EasyEdit\utils\MixedUtils;
use pocketmine\block\BlockFactory;
use pocketmine\math\Vector3;
use pocketmine\world\World;

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
		//TODO: make this optional
		return new StaticBlockListSelection("", new Vector3(0, World::Y_MIN, 0), new Vector3(0, World::Y_MIN, 0));
	}

	/**
	 * @param string $time
	 * @param string $changed
	 */
	public function notifyUser(string $time, string $changed): void
	{
		$this->sendOutputPacket(new MessageSendData(Messages::replace("blocks-counted", ["{time}" => $time, "{changed}" => (string) array_sum($this->counted)])));
		$msg = "";
		foreach ($this->counted as $block => $count) {
			$msg .= BlockFactory::getInstance()->fromFullBlock($block)->getName() . ": " . MixedUtils::humanReadable($count) . "\n";
		}
		$this->sendOutputPacket(new MessageSendData($msg, false));
	}

	public function executeEdit(EditTaskHandler $handler): void
	{
		$this->getCurrentSelection()->useOnBlocks(function (int $x, int $y, int $z) use ($handler): void {
			$id = $handler->getBlock($x, $y, $z);
			if (isset($this->counted[$id])) {
				$this->counted[$id]++;
			} else {
				$this->counted[$id] = 1;
			}
		}, SelectionContext::full(), $this->getTotalSelection());
	}
}