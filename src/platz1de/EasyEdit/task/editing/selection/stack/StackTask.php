<?php

namespace platz1de\EasyEdit\task\editing\selection\stack;

use Generator;
use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\GroupedChunkHandler;
use platz1de\EasyEdit\task\editing\selection\cubic\CubicStaticUndo;
use platz1de\EasyEdit\task\editing\selection\SelectionEditTask;
use platz1de\EasyEdit\task\editing\SingleChunkHandler;
use platz1de\EasyEdit\task\editing\type\SettingNotifier;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\world\HeightMapCache;
use pocketmine\block\Block;
use pocketmine\math\Axis;
use pocketmine\math\Vector3;
use pocketmine\world\World;
use UnexpectedValueException;

class StackTask extends SelectionEditTask
{
	use CubicStaticUndo;
	use SettingNotifier;

	private Selection $original;

	private Vector3 $direction;
	private bool $insert;

	/**
	 * @param Selection $selection
	 * @param Vector3   $direction
	 * @param bool      $insert
	 */
	public function __construct(Selection $selection, Vector3 $direction, bool $insert = false)
	{
		$this->insert = $insert;
		$this->direction = $direction;
		parent::__construct($selection);
	}

	public function execute(): void
	{
		$this->original = $this->selection;
		$this->selection = new StackingHelper($this->selection, $this->direction);
		parent::execute();
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "stack";
	}

	/**
	 * @param EditTaskhandler $handler
	 * @return Generator<ShapeConstructor>
	 */
	public function prepareConstructors(EditTaskHandler $handler): Generator
	{
		$originalSize = $this->original->getPos2()->subtractVector($this->original->getPos1())->add(1, 1, 1);
		$sizeX = $originalSize->getFloorX();
		$sizeY = $originalSize->getFloorY();
		$sizeZ = $originalSize->getFloorZ();
		$start = $this->original->getPos1();
		$startX = $start->getFloorX();
		$startY = $start->getFloorY();
		$startZ = $start->getFloorZ();
		if (!$this->selection instanceof StackingHelper) {
			throw new UnexpectedValueException("Selection is not a StackingHelper");
		}
		if ($this->insert) {
			$ignore = HeightMapCache::getIgnore();
			yield from $this->selection->asShapeConstructors(function (int $x, int $y, int $z) use ($ignore, $handler, $sizeX, $sizeY, $sizeZ, $startX, $startY, $startZ): void {
				$block = $handler->getBlockUnsafe($startX + (($x - $startX) % $sizeX + $sizeX) % $sizeX, $startY + (($y - $startY) % $sizeY + $sizeY) % $sizeY, $startZ + (($z - $startZ) % $sizeZ + $sizeZ) % $sizeZ);
				if ($block !== null && $block !== 0 && in_array($handler->getBlock($x, $y, $z) >> Block::INTERNAL_METADATA_BITS, $ignore, true)) {
					$handler->changeBlock($x, $y, $z, $block);
				}
			}, $this->context);
		} else {
			yield from $this->selection->asShapeConstructors(function (int $x, int $y, int $z) use ($handler, $sizeX, $sizeY, $sizeZ, $startX, $startY, $startZ): void {
				$handler->copyBlockUnsafe($x, $y, $z, $startX + (($x - $startX) % $sizeX + $sizeX) % $sizeX, $startY + (($y - $startY) % $sizeY + $sizeY) % $sizeY, $startZ + (($z - $startZ) % $sizeZ + $sizeZ) % $sizeZ);
			}, $this->context);
		}
	}

	/**
	 * @return GroupedChunkHandler
	 */
	public function getChunkHandler(): GroupedChunkHandler
	{
		if (!$this->selection instanceof StackingHelper) {
			throw new UnexpectedValueException("Selection is not a StackingHelper");
		}
		if ($this->selection->getAxis() === Axis::Y) {
			return new SingleChunkHandler($this->getWorld());
		}
		if ($this->selection->isCopying()) {
			return new CopyingStackingChunkHandler($this->getWorld(), $this->original, $this->selection->getAxis(), $this->selection->getAmount());
		}
		return new SimpleStackingChunkHandler($this->getWorld(), $this->original, $this->selection->getAxis());
	}

	protected function sortChunks(array $chunks): array
	{
		if ($this->direction->getFloorX() !== 0) {
			usort($chunks, static function (int $a, int $b): int {
				World::getXZ($a, $aX, $aZ);
				World::getXZ($b, $bX, $bZ);
				return $aZ - $bZ;
			});
		} else if ($this->direction->getFloorZ() !== 0) {
			usort($chunks, static function (int $a, int $b): int {
				World::getXZ($a, $aX, $aZ);
				World::getXZ($b, $bX, $bZ);
				return $aX - $bX;
			});
		}
		//No sorting needed for y-Axis
		return $chunks;
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);
		$stream->putVector($this->direction);
		$stream->putBool($this->insert);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);
		$this->direction = $stream->getVector();
		$this->insert = $stream->getBool();
	}
}