<?php

namespace platz1de\EasyEdit\task\editing;

use Generator;
use platz1de\EasyEdit\result\CountingTaskResult;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
use platz1de\EasyEdit\selection\NonSavingBlockListSelection;

//TODO: Pull this out of EditTask (move selection logic in underlying classes)
class CountTask extends SelectionEditTask
{
	/**
	 * @var int[]
	 */
	private array $counted = [];

	protected function toTaskResult(): CountingTaskResult
	{
		return new CountingTaskResult($this->counted);
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "count";
	}

	/**
	 * @return NonSavingBlockListSelection
	 */
	public function createUndoBlockList(): BlockListSelection
	{
		return new NonSavingBlockListSelection();
	}

	/**
	 * @param EditTaskHandler $handler
	 * @return Generator<ShapeConstructor>
	 */
	public function prepareConstructors(EditTaskHandler $handler): Generator
	{
		yield from $this->selection->asShapeConstructors(function (int $x, int $y, int $z) use ($handler): void {
			$id = $handler->getBlock($x, $y, $z);
			if (isset($this->counted[$id])) {
				$this->counted[$id]++;
			} else {
				$this->counted[$id] = 1;
			}
		}, $this->context);
	}
}