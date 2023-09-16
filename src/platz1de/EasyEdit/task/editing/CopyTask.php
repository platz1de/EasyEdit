<?php

namespace platz1de\EasyEdit\task\editing;

use Generator;
use platz1de\EasyEdit\math\OffGridBlockVector;
use platz1de\EasyEdit\result\EditTaskResult;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use platz1de\EasyEdit\selection\NonSavingBlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\TileUtils;

class CopyTask extends SelectionEditTask
{
	private DynamicBlockListSelection $result;

	/**
	 * @param Selection             $selection
	 * @param OffGridBlockVector    $position
	 * @param SelectionContext|null $context
	 */
	public function __construct(Selection $selection, private OffGridBlockVector $position, ?SelectionContext $context = null)
	{
		parent::__construct($selection, $context);
	}

	public function calculateEffectiveComplexity(): int
	{
		return $this->getSelection()->getSize()->volume() / 2;
	}

	protected function toTaskResult(): EditTaskResult
	{
		return new EditTaskResult($this->result->getBlockCount(), StorageModule::store($this->result));
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "copy";
	}

	/**
	 * @return BlockListSelection
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
		$result = $this->result = DynamicBlockListSelection::fromWorldPositions($this->position, $this->getSelection()->getPos1(), $this->getSelection()->getPos2());
		$ox = $result->getWorldOffset()->x;
		$oy = $result->getWorldOffset()->y;
		$oz = $result->getWorldOffset()->z;
		yield from $this->getSelection()->asShapeConstructors(function (int $x, int $y, int $z) use ($ox, $oy, $oz, $handler, $result): void {
			$result->addBlock($x - $ox, $y - $oy, $z - $oz, $handler->getBlock($x, $y, $z));
			$result->addTile(TileUtils::offsetCompound($handler->getTile($x, $y, $z), -$ox, -$oy, -$oz));
		}, $this->context);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putBlockVector($this->position);
		parent::putData($stream);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->position = $stream->getOffGridBlockVector();
		parent::parseData($stream);
	}
}