<?php

namespace platz1de\EasyEdit\task\editing\selection;

use Generator;
use platz1de\EasyEdit\selection\BinaryBlockListStream;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\task\editing\EditTaskHandler;

class StreamPasteTask extends SelectionEditTask
{
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

	/**
	 * @param EditTaskhandler $handler
	 * @return Generator<ShapeConstructor>
	 */
	public function prepareConstructors(EditTaskHandler $handler): Generator
	{
		//WARNING: This isn't the default closure style
		yield from $this->selection->asShapeConstructors(function (int $x, int $y, int $z, int $block) use ($handler): void {
			$handler->changeBlock($x, $y, $z, $block);
		}, $this->context);
	}


	/**
	 * @return BlockListSelection
	 */
	public function createUndoBlockList(): BlockListSelection
	{
		return new BinaryBlockListStream($this->getWorld());
	}
}