<?php

namespace platz1de\EasyEdit\task\editing;

use Generator;
use platz1de\EasyEdit\math\OffGridBlockVector;
use platz1de\EasyEdit\result\CuttingTaskResult;
use platz1de\EasyEdit\result\EditTaskResult;
use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\task\editing\cubic\CubicStaticUndo;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\TileUtils;
use pocketmine\block\VanillaBlocks;

//TODO: Remove EditTask inheritance
class CutTask extends SelectionEditTask
{
	use CubicStaticUndo;

	private DynamicBlockListSelection $result;

	/**
	 * @param Selection          $selection
	 * @param OffGridBlockVector $position
	 */
	public function __construct(Selection $selection, private OffGridBlockVector $position)
	{
		parent::__construct($selection);
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "cut";
	}

	protected function toTaskResult(): CuttingTaskResult
	{
		return new CuttingTaskResult($this->handler->getChangedBlockCount(), StorageModule::store($this->undo), StorageModule::store($this->result));
	}

	public function executeInternal(): EditTaskResult
	{
		$this->result = DynamicBlockListSelection::fromWorldPositions($this->position, $this->selection->getPos1(), $this->selection->getPos2());
		return parent::executeInternal();
	}

	/**
	 * @param EditTaskHandler $handler
	 * @return Generator<ShapeConstructor>
	 */
	public function prepareConstructors(EditTaskHandler $handler): Generator
	{
		$result = $this->result;
		$id = VanillaBlocks::AIR()->getStateId();
		$ox = $result->getWorldOffset()->x;
		$oy = $result->getWorldOffset()->y;
		$oz = $result->getWorldOffset()->z;

		yield from $this->selection->asShapeConstructors(function (int $x, int $y, int $z) use ($id, $handler, $result, $ox, $oy, $oz): void {
			$result->addBlock($x - $ox, $y - $oy, $z - $oz, $handler->getBlock($x, $y, $z));
			$result->addTile(TileUtils::offsetCompound($handler->getTile($x, $y, $z), -$ox, -$oy, -$oz));
			$handler->changeBlock($x, $y, $z, $id);
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