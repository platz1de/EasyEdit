<?php

namespace platz1de\EasyEdit\task\editing\selection;

use platz1de\EasyEdit\handler\EditHandler;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\selection\StaticBlockListSelection;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\thread\output\session\MessageSendData;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\MixedUtils;
use pocketmine\block\BlockFactory;
use pocketmine\math\Vector3;
use pocketmine\world\Position;
use pocketmine\world\World;

class CountTask extends SelectionEditTask
{
	/**
	 * @param string                $world
	 * @param AdditionalDataManager $data
	 * @param Selection             $selection
	 * @param Vector3               $position
	 * @param Vector3               $splitOffset
	 * @return CountTask
	 */
	public static function from(string $world, AdditionalDataManager $data, Selection $selection, Vector3 $position, Vector3 $splitOffset): CountTask
	{
		$instance = new self($world, $data, $position);
		SelectionEditTask::initSelection($instance, $selection, $splitOffset);
		return $instance;
	}

	/**
	 * @param Session   $session
	 * @param Selection $selection
	 * @param Position  $place
	 */
	public static function queue(Session $session, Selection $selection, Position $place): void
	{
		EditHandler::runPlayerTask($session, self::from($selection->getWorldName(), new AdditionalDataManager(false, false), $selection, $place->asVector3(), Vector3::zero()));
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
	 * @param int                   $taskId
	 * @param string                $time
	 * @param string                $changed
	 * @param AdditionalDataManager $data
	 */
	public static function notifyUser(int $taskId, string $time, string $changed, AdditionalDataManager $data): void
	{
		EditThread::getInstance()->sendOutput(new MessageSendData($taskId, Messages::replace("blocks-counted", ["{time}" => $time, "{changed}" => (string) array_sum($data->getCountedBlocks())])));
		$msg = "";
		foreach ($data->getCountedBlocks() as $block => $count) {
			$msg .= BlockFactory::getInstance()->fromFullBlock($block)->getName() . ": " . MixedUtils::humanReadable($count) . "\n";
		}
		EditThread::getInstance()->sendOutput(new MessageSendData($taskId, $msg, false));
	}

	public function executeEdit(EditTaskHandler $handler): void
	{
		$blocks = $this->getDataManager()->getCountedBlocks();
		$this->getCurrentSelection()->useOnBlocks(function (int $x, int $y, int $z) use ($handler, &$blocks): void {
			$id = $handler->getBlock($x, $y, $z);
			if (isset($blocks[$id])) {
				$blocks[$id]++;
			} else {
				$blocks[$id] = 1;
			}
		}, SelectionContext::full(), $this->getTotalSelection());
		arsort($blocks, SORT_NUMERIC);
		$this->getDataManager()->setCountedBlocks($blocks);
	}
}