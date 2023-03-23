<?php

namespace platz1de\EasyEdit\task\editing\selection\move;

use Generator;
use platz1de\EasyEdit\math\BlockOffsetVector;
use platz1de\EasyEdit\math\BlockVector;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\StaticBlockListSelection;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\GroupedChunkHandler;
use platz1de\EasyEdit\task\editing\selection\SelectionEditTask;
use platz1de\EasyEdit\task\editing\type\SettingNotifier;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\block\VanillaBlocks;
use pocketmine\world\World;

class MoveTask extends SelectionEditTask
{
	use SettingNotifier;

	/**
	 * @param Selection         $selection
	 * @param BlockOffsetVector $direction
	 */
	public function __construct(Selection $selection, private BlockOffsetVector $direction)
	{
		parent::__construct($selection);
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "move";
	}

	/**
	 * @param EditTaskHandler $handler
	 * @return Generator<ShapeConstructor>
	 */
	public function prepareConstructors(EditTaskHandler $handler): Generator
	{
		$dx = $this->direction->x;
		$dy = $this->direction->y;
		$dz = $this->direction->z;
		//TODO: change order of iteration to optimize performance
		$air = VanillaBlocks::AIR()->getStateId();
		yield from $this->selection->asShapeConstructors(function (int $x, int $y, int $z) use ($handler, $air): void {
			$handler->changeBlock($x, $y, $z, $air); //Make sure we don't overwrite anything
		}, $this->context);
		yield from $this->selection->asShapeConstructors(function (int $x, int $y, int $z) use ($handler, $dx, $dy, $dz): void {
			$handler->copyBlock($x + $dx, $y + $dy, $z + $dz, $x, $y, $z, false);
		}, $this->context);
	}

	protected function sortChunks(array $chunks): array
	{
		if ($this->direction->x === 0 && $this->direction->z !== 0) {
			$z = array_map(static function (int $c): int {
				World::getXZ($c, $x, $z);
				return $z;
			}, $chunks);
			array_multisort($z, $this->direction->z > 0 ? SORT_DESC : SORT_ASC, $chunks);
			return $chunks;
		}
		$x = array_map(static function (int $c): int {
			World::getXZ($c, $x, $z);
			return $x;
		}, $chunks);
		$z = array_map(static function (int $c): int {
			World::getXZ($c, $x, $z);
			return $z;
		}, $chunks);
		array_multisort($x, $this->direction->x > 0 ? SORT_DESC : SORT_ASC, $z, $this->direction->z > 0 ? SORT_DESC : SORT_ASC, $chunks);
		return $chunks;
	}

	public function getChunkHandler(): GroupedChunkHandler
	{
		return new MovingChunkHandler($this->getWorld(), $this->selection, $this->direction);
	}

	/**
	 * @return BlockListSelection
	 */
	public function createUndoBlockList(): BlockListSelection
	{
		return new StaticBlockListSelection($this->getWorld(), BlockVector::minComponents($this->getSelection()->getPos1(), $this->getSelection()->getPos1()->offset($this->direction)), BlockVector::maxComponents($this->getSelection()->getPos2(), $this->getSelection()->getPos2()->offset($this->direction)));
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);
		$stream->putBlockVector($this->direction);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);
		$this->direction = $stream->getOffsetVector();
	}
}