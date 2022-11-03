<?php

namespace platz1de\EasyEdit\task\editing\selection;

use platz1de\EasyEdit\selection\BinaryBlockListStream;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\type\PastingNotifier;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\math\Vector3;
use pocketmine\world\World;

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

	public function executeEdit(EditTaskHandler $handler, int $chunk): void
	{
		//WARNING: This isn't the default closure style
		$this->selection->useOnBlocks(function (int $x, int $y, int $z, int $block) use ($handler): void {
			$handler->changeBlock($x, $y, $z, $block);
		}, $this->context, $chunk);

		$min = VectorUtils::getChunkPosition($chunk);
		$max = $min->add(15, World::Y_MAX - World::Y_MIN - 1, 15);
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