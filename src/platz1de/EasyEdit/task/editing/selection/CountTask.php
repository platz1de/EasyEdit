<?php

namespace platz1de\EasyEdit\task\editing\selection;

use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\selection\StaticBlockListSelection;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\thread\EditThread;
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
	 * @var int[][]
	 */
	private static array $stupidHack = [];

	/**
	 * @param string    $world
	 * @param Selection $selection
	 * @param Vector3   $position
	 * @return CountTask
	 */
	public static function from(string $world, Selection $selection, Vector3 $position): CountTask
	{
		$instance = new self($world, $position);
		$instance->selection = $selection;
		return $instance;
	}

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
	 * @param int    $taskId
	 * @param string $time
	 * @param string $changed
	 */
	public static function notifyUser(int $taskId, string $time, string $changed): void
	{
		EditThread::getInstance()->sendOutput(new MessageSendData($taskId, Messages::replace("blocks-counted", ["{time}" => $time, "{changed}" => (string) array_sum(self::$stupidHack[$taskId])])));
		$msg = "";
		foreach (self::$stupidHack[$taskId] as $block => $count) {
			$msg .= BlockFactory::getInstance()->fromFullBlock($block)->getName() . ": " . MixedUtils::humanReadable($count) . "\n";
		}
		EditThread::getInstance()->sendOutput(new MessageSendData($taskId, $msg, false));
		unset(self::$stupidHack[$taskId]);
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
		self::$stupidHack[$this->getTaskId()] = $this->counted;
	}
}