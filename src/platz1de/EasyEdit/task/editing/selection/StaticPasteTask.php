<?php

namespace platz1de\EasyEdit\task\editing\selection;

use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\selection\StaticBlockListSelection;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\selection\cubic\CubicStaticUndo;
use platz1de\EasyEdit\task\editing\type\PastingNotifier;
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
	 * @param string                   $world
	 * @param StaticBlockListSelection $selection
	 * @param Vector3                  $position
	 * @return StaticPasteTask
	 */
	public static function from(string $world, StaticBlockListSelection $selection, Vector3 $position): StaticPasteTask
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
		return "static_paste";
	}

	public function executeEdit(EditTaskHandler $handler): void
	{
		$selection = $this->current;
		$selection->useOnBlocks(function (int $x, int $y, int $z) use ($handler, $selection): void {
			$block = $selection->getIterator()->getBlock($x, $y, $z);
			if (Selection::processBlock($block)) {
				$handler->changeBlock($x, $y, $z, $block);
			}
		}, SelectionContext::full(), $this->getTotalSelection());

		foreach ($selection->getTiles() as $tile) {
			$handler->addTile($tile);
		}
	}
}