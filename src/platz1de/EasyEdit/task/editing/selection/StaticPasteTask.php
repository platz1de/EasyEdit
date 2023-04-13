<?php

namespace platz1de\EasyEdit\task\editing\selection;

use Generator;
use platz1de\EasyEdit\selection\BinaryBlockListStream;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
use platz1de\EasyEdit\selection\identifier\SelectionIdentifier;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\StaticBlockListSelection;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\selection\cubic\CubicStaticUndo;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\world\World;

class StaticPasteTask extends SelectionEditTask
{
	use CubicStaticUndo {
		CubicStaticUndo::createUndoBlockList as private getDefaultBlockList;
	}

	/**
	 * @phpstan-var StaticBlockListSelection|BinaryBlockListStream
	 */
	protected Selection $selection;

	/**
	 * @param SelectionIdentifier $selection
	 */
	public function __construct(SelectionIdentifier $selection)
	{
		parent::__construct(StorageModule::mustGetStatic($selection));
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "static_paste";
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
		foreach ($this->selection->getTiles($min, $max) as $tile) {
			$handler->addTile($tile);
		}
	}

	/**
	 * @param EditTaskhandler $handler
	 * @return Generator<ShapeConstructor>
	 */
	public function prepareConstructors(EditTaskHandler $handler): Generator
	{
		$selection = $this->selection;
		if ($selection instanceof BinaryBlockListStream) {
			//WARNING: This isn't the default closure style
			yield from $this->selection->asShapeConstructors(function (int $x, int $y, int $z, int $block) use ($handler): void {
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
		return $this->selection instanceof BinaryBlockListStream ? new BinaryBlockListStream($this->getWorld()) : $this->getDefaultBlockList();
	}
}