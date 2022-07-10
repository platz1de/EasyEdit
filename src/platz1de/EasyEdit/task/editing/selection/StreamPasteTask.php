<?php

namespace platz1de\EasyEdit\task\editing\selection;

use platz1de\EasyEdit\selection\BinaryBlockListStream;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\type\PastingNotifier;
use pocketmine\math\Vector3;

class StreamPasteTask extends SelectionEditTask
{
	use PastingNotifier;

	/**
	 * @var BinaryBlockListStream
	 */
	protected Selection $current;

	/**
	 * @param string                $world
	 * @param BinaryBlockListStream $selection
	 * @param Vector3               $position
	 * @return StreamPasteTask
	 */
	public static function from(string $world, BinaryBlockListStream $selection, Vector3 $position): StreamPasteTask
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
		return "stream_paste";
	}

	public function executeEdit(EditTaskHandler $handler): void
	{
		//WARNING: This isn't the default closure style
		$this->current->useOnBlocks(function (int $x, int $y, int $z, int $block) use ($handler): void {
			$handler->changeBlock($x, $y, $z, $block);
		}, SelectionContext::full(), $this->getTotalSelection());

		foreach ($this->current->getTiles() as $tile) {
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