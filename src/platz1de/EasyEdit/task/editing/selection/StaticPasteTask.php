<?php

namespace platz1de\EasyEdit\task\editing\selection;

use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\selection\StaticBlockListSelection;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\selection\cubic\CubicStaticUndo;
use platz1de\EasyEdit\task\editing\type\PastingNotifier;
use platz1de\EasyEdit\thread\input\TaskInputData;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use pocketmine\math\Vector3;

class StaticPasteTask extends SelectionEditTask
{
	use CubicStaticUndo;
	use PastingNotifier;

	/**
	 * @var StaticBlockListSelection
	 */
	protected Selection $current;

	/**
	 * @param string                   $owner
	 * @param string                   $world
	 * @param AdditionalDataManager    $data
	 * @param StaticBlockListSelection $selection
	 * @param Vector3                  $position
	 * @param Vector3                  $splitOffset
	 * @return StaticPasteTask
	 */
	public static function from(string $owner, string $world, AdditionalDataManager $data, StaticBlockListSelection $selection, Vector3 $position, Vector3 $splitOffset): StaticPasteTask
	{
		$instance = new self($owner, $world, $data, $position);
		SelectionEditTask::initSelection($instance, $selection, $splitOffset);
		return $instance;
	}

	/**
	 * @param StaticBlockListSelection $selection
	 */
	public static function queue(StaticBlockListSelection $selection): void
	{
		TaskInputData::fromTask(self::from($selection->getPlayer(), $selection->getWorldName(), new AdditionalDataManager(true, true), $selection, Vector3::zero(), Vector3::zero()));
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "static_paste";
	}

	public function executeEdit(EditTaskHandler $handler): void
	{
		$selection = $this->current;
		$selection->useOnBlocks(function (int $x, int $y, int $z) use ($handler, $selection): void {
			$block = $selection->getIterator()->getBlockAt($x, $y, $z);
			if (Selection::processBlock($block)) {
				$handler->changeBlock($x, $y, $z, $block);
			}
		}, SelectionContext::full(), $this->getTotalSelection());

		foreach ($selection->getTiles() as $tile) {
			$handler->addTile($tile);
		}
	}
}