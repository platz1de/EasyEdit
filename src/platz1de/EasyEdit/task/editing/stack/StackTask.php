<?php

namespace platz1de\EasyEdit\task\editing\stack;

use Generator;
use platz1de\EasyEdit\math\BlockOffsetVector;
use platz1de\EasyEdit\result\EditTaskResult;
use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\task\editing\cubic\CubicStaticUndo;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\GroupedChunkHandler;
use platz1de\EasyEdit\task\editing\SelectionEditTask;
use platz1de\EasyEdit\task\editing\SingleChunkHandler;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\world\HeightMapCache;
use pocketmine\block\Block;
use pocketmine\math\Axis;
use pocketmine\world\World;
use UnexpectedValueException;

class StackTask extends SelectionEditTask
{
	use CubicStaticUndo;

	private Selection $original;
	private StackingHelper $helper;

	/**
	 * @param Selection         $selection
	 * @param BlockOffsetVector $direction
	 * @param bool              $insert
	 */
	public function __construct(Selection $selection, private BlockOffsetVector $direction, private bool $insert = false)
	{
		parent::__construct($selection);
	}

	public function calculateEffectiveComplexity(): int
	{
		return $this->getSelection()->getSize()->volume() * $this->direction->cubicVolume();
	}

	public function getSelection(): StackingHelper
	{
		if (!isset($this->helper)) {
			$this->helper = new StackingHelper($this->original = parent::getSelection(), $this->direction);
		}
		return $this->helper;
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
		$originalSize = $this->original->getSize();
		$sizeX = $originalSize->x;
		$sizeY = $originalSize->y;
		$sizeZ = $originalSize->z;
		$start = $this->original->getPos1();
		$startX = $start->x;
		$startY = $start->y;
		$startZ = $start->z;
		if ($this->insert) {
			$ignore = HeightMapCache::getIgnore();
			yield from $this->getSelection()->asShapeConstructors(function (int $x, int $y, int $z) use ($ignore, $handler, $sizeX, $sizeY, $sizeZ, $startX, $startY, $startZ): void {
				$block = $handler->getBlock($startX + (($x - $startX) % $sizeX + $sizeX) % $sizeX, $startY + (($y - $startY) % $sizeY + $sizeY) % $sizeY, $startZ + (($z - $startZ) % $sizeZ + $sizeZ) % $sizeZ);
				if ($block !== 0 && in_array($handler->getBlock($x, $y, $z) >> Block::INTERNAL_STATE_DATA_BITS, $ignore, true)) {
					$handler->changeBlock($x, $y, $z, $block);
				}
			}, $this->context);
		} else {
			yield from $this->getSelection()->asShapeConstructors(function (int $x, int $y, int $z) use ($handler, $sizeX, $sizeY, $sizeZ, $startX, $startY, $startZ): void {
				$handler->copyBlock($x, $y, $z, $startX + (($x - $startX) % $sizeX + $sizeX) % $sizeX, $startY + (($y - $startY) % $sizeY + $sizeY) % $sizeY, $startZ + (($z - $startZ) % $sizeZ + $sizeZ) % $sizeZ);
			}, $this->context);
		}
	}

	/**
	 * @return GroupedChunkHandler
	 */
	public function getChunkHandler(): GroupedChunkHandler
	{
		if ($this->getSelection()->getAxis() === Axis::Y) {
			return new SingleChunkHandler($this->getTargetWorld());
		}
		if ($this->getSelection()->isCopying()) {
			return new CopyingStackingChunkHandler($this->getTargetWorld(), $this->original, $this->getSelection()->getAxis());
		}
		return new SimpleStackingChunkHandler($this->getTargetWorld(), $this->original, $this->getSelection()->getAxis());
	}

	protected function sortChunks(array $chunks): array
	{
		if ($this->direction->x !== 0) {
			usort($chunks, static function (int $a, int $b): int {
				World::getXZ($a, $aX, $aZ);
				World::getXZ($b, $bX, $bZ);
				return $aZ - $bZ;
			});
		} else if ($this->direction->z !== 0) {
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
		$stream->putBlockVector($this->direction);
		$stream->putBool($this->insert);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);
		$this->direction = $stream->getOffsetVector();
		$this->insert = $stream->getBool();
	}
}