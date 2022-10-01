<?php

namespace platz1de\EasyEdit\task\editing\selection;

use platz1de\EasyEdit\selection\BinaryBlockListStream;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\type\PastingNotifier;
use pocketmine\math\Vector3;

class StreamPasteTask extends SelectionEditTask
{
	use PastingNotifier;

	/**
	 * @var BinaryBlockListStream
	 */
	protected Selection $selection;

	/**
	 * @param BinaryBlockListStream $selection
	 */
	public function __construct(BinaryBlockListStream $selection)
	{
		parent::__construct($selection);
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "stream_paste";
	}

	public function executeEdit(EditTaskHandler $handler, Vector3 $min, Vector3 $max): void
	{
		//WARNING: This isn't the default closure style
		$this->selection->useOnBlocks(function (int $x, int $y, int $z, int $block) use ($handler): void {
			$handler->changeBlock($x, $y, $z, $block);
		}, $this->context, $min, $max);

		foreach ($this->selection->getTiles($min, $max) as $tile) {
			$handler->addTile($tile);
		}
	}


	/**
	 * @return BlockListSelection
	 */
	public function getUndoBlockList(): BlockListSelection
	{
		return new BinaryBlockListStream($this->getWorld());
	}
}