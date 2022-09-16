<?php

namespace platz1de\EasyEdit\task\editing\selection;

use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\selection\cubic\CubicStaticUndo;
use platz1de\EasyEdit\task\editing\type\SettingNotifier;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\world\HeightMapCache;
use pocketmine\block\Block;
use pocketmine\math\Vector3;

class StackTask extends SelectionEditTask
{
	use CubicStaticUndo;
	use SettingNotifier;

	private Vector3 $direction;
	private bool $insert;

	/**
	 * @param Selection $selection
	 * @param bool        $insert
	 */
	public function __construct(Selection $selection, Vector3 $direction, bool $insert = false)
	{
		$this->insert = $insert;
		parent::__construct($selection);
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "stack";
	}

	public function executeEdit(EditTaskHandler $handler, Vector3 $min, Vector3 $max): void
	{
		$originalSize = $this->selection->getPos2()->subtractVector($selection->getPos1())->add(1, 1, 1);
		$sizeX = $originalSize->getFloorX();
		$sizeY = $originalSize->getFloorY();
		$sizeZ = $originalSize->getFloorZ();
		$start = $selection->getPos1();
		$startX = $start->getFloorX();
		$startY = $start->getFloorY();
		$startZ = $start->getFloorZ();
		if ($this->insert) {
			$ignore = HeightMapCache::getIgnore();
			$this->selection->useOnBlocks(function (int $x, int $y, int $z) use ($ignore, $handler, $sizeX, $sizeY, $sizeZ, $startX, $startY, $startZ): void {
				$block = $handler->getBlock($startX + (($x - $startX) % $sizeX + $sizeX) % $sizeX, $startY + (($y - $startY) % $sizeY + $sizeY) % $sizeY, $startZ + (($z - $startZ) % $sizeZ + $sizeZ) % $sizeZ);
				if ($block !== 0 && in_array($handler->getBlock($x, $y, $z) >> Block::INTERNAL_METADATA_BITS, $ignore, true)) {
					$handler->changeBlock($x, $y, $z, $block);
				}
			}, $this->context, $min, $max);
		} else {
			$this->selection->useOnBlocks(function (int $x, int $y, int $z) use ($handler, $sizeX, $sizeY, $sizeZ, $startX, $startY, $startZ): void {
				$handler->copyBlock($x, $y, $z, $startX + (($x - $startX) % $sizeX + $sizeX) % $sizeX, $startY + (($y - $startY) % $sizeY + $sizeY) % $sizeY, $startZ + (($z - $startZ) % $sizeZ + $sizeZ) % $sizeZ);
			}, $this->context, $min, $max);
		}
	}

	protected function orderChunks(array $chunks): array
	{
		$size = $this->selection->getPos2()->subtractVector($selection->getPos1())->add(1, 1, 1);
		if ($this->direction->getFloorX() !== 0) {
			usort($chunks, static function (int $a, int $b): int {
				World::getXZ($a, $aX, $aZ);
				World::getXZ($b, $bX, $bZ);
				return $aX - $bX;
			});
		} else if ($this->direction->getFloorZ() !== 0){
			usort($chunks, static function (int $a, int $b): int {
				World::getXZ($a, $aX, $aZ);
				World::getXZ($b, $bX, $bZ);
				return $aZ - $bZ;
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