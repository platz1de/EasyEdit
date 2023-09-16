<?php

namespace platz1de\EasyEdit\task\editing;

use Generator;
use InvalidArgumentException;
use platz1de\EasyEdit\selection\BinaryBlockListStream;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
use platz1de\EasyEdit\selection\identifier\BlockListSelectionIdentifier;
use platz1de\EasyEdit\selection\StaticBlockListSelection;
use platz1de\EasyEdit\task\editing\cubic\CubicStaticUndo;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\world\World;

class StaticPasteTask extends SelectionEditTask
{
	use CubicStaticUndo {
		CubicStaticUndo::createUndoBlockList as private getDefaultBlockList;
	}

	/**
	 * @param BlockListSelectionIdentifier $selection
	 */
	public function __construct(BlockListSelectionIdentifier $selection)
	{
		parent::__construct($selection);
	}

	public function calculateEffectiveComplexity(): int
	{
		return -1;
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "static_paste";
	}

	/**
	 * @return StaticBlockListSelection|BinaryBlockListStream
	 */
	public function getSelection(): StaticBlockListSelection|BinaryBlockListStream
	{
		$sel = parent::getSelection();
		if (!$sel instanceof StaticBlockListSelection && !$sel instanceof BinaryBlockListStream) {
			throw new InvalidArgumentException("Selection must be a static block list");
		}
		return $sel;
	}

	/**
	 * @param EditTaskHandler $handler
	 * @param int             $chunk
	 */
	public function executeEdit(EditTaskHandler $handler, int $chunk): void
	{
		parent::executeEdit($handler, $chunk);
		$min = VectorUtils::getChunkPosition($chunk);
		$max = $min->add(15, World::Y_MAX - World::Y_MIN - 1, 15);
		foreach ($this->getSelection()->getTiles($min, $max) as $tile) {
			$handler->addTile($tile);
		}
	}

	/**
	 * @param EditTaskHandler $handler
	 * @return Generator<ShapeConstructor>
	 */
	public function prepareConstructors(EditTaskHandler $handler): Generator
	{
		$selection = $this->getSelection();
		if ($selection instanceof BinaryBlockListStream) {
			//WARNING: This isn't the default closure style
			yield from $selection->asShapeConstructors(function (int $x, int $y, int $z, int $block) use ($handler): void {
				$handler->changeBlock($x, $y, $z, $block);
			}, $this->context);
		} else {
			yield from $selection->asShapeConstructors(function (int $x, int $y, int $z) use ($handler, $selection): void {
				$block = $selection->getIterator()->getBlock($x, $y, $z);
				if ($block !== 0) {
					$handler->changeBlock($x, $y, $z, $block);
				}
			}, $this->context);
		}
	}

	public function createUndoBlockList(): BlockListSelection
	{
		return $this->getSelection() instanceof BinaryBlockListStream ? new BinaryBlockListStream($this->getTargetWorld()) : $this->getDefaultBlockList();
	}
}