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
	protected Selection $selection;

	/**
	 * @param StaticBlockListSelection $selection
	 */
	public function __construct(StaticBlockListSelection $selection)
	{
		parent::__construct($selection);
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "static_paste";
	}

	public function executeEdit(EditTaskHandler $handler, Vector3 $min, Vector3 $max): void
	{
		$selection = $this->selection;
		$selection->useOnBlocks(function (int $x, int $y, int $z) use ($handler, $selection): void {
			$block = $selection->getIterator()->getBlock($x, $y, $z);
			if (Selection::processBlock($block)) {
				$handler->changeBlock($x, $y, $z, $block);
			}
		}, SelectionContext::full(), $this->getTotalSelection(), $min, $max);

		foreach ($selection->getTiles() as $tile) {
			$handler->addTile($tile);
		}
	}
}