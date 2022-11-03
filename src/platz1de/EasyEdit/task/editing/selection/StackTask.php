<?php

namespace platz1de\EasyEdit\task\editing\selection;

use platz1de\EasyEdit\selection\helper\StackingHelper;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\selection\cubic\CubicStaticUndo;
use platz1de\EasyEdit\task\editing\type\SettingNotifier;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\world\HeightMapCache;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class StackTask extends SelectionEditTask
{
	use CubicStaticUndo;
	use SettingNotifier;

	private Selection $helper;

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
		$this->helper = $this->selection;
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

	public function executeEdit(EditTaskHandler $handler, Vector3 $min, Vector3 $max): void
	{
		//TODO: chunkloading
		return;
		$originalSize = $this->helper->getPos2()->subtractVector($this->helper->getPos1())->add(1, 1, 1);
		$sizeX = $originalSize->getFloorX();
		$sizeY = $originalSize->getFloorY();
		$sizeZ = $originalSize->getFloorZ();
		$start = $this->helper->getPos1();
		$startX = $start->getFloorX();
		$startY = $start->getFloorY();
		$startZ = $start->getFloorZ();
		$dMin = Vector3::maxComponents($min, $this->selection->getPos1())->subtractVector($start);
		$dMax = Vector3::minComponents($max, $this->selection->getPos2())->subtractVector($start);
		$chunks = [];
		for ($x = $startX + ($dMin->getX() % $sizeX + $sizeX) % $sizeX >> 4; $x <= $startX + ($dMax->getX() % $sizeX + $sizeX) % $sizeX >> 4; $x++) {
			for ($z = $startZ + ($dMin->getZ() % $sizeZ + $sizeZ) % $sizeZ >> 4; $z <= $startZ + ($dMax->getZ() % $sizeZ + $sizeZ) % $sizeZ >> 4; $z++) {
				$chunks[] = World::chunkHash($x, $z);
			}
		}
		$this->requestRuntimeChunks($handler, $chunks);
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

	protected function sortChunks(array $chunks): array
	{
		if ($this->direction->getFloorX() !== 0) {
			usort($chunks, static function (int $a, int $b): int {
				World::getXZ($a, $aX, $aZ);
				World::getXZ($b, $bX, $bZ);
				return $aX - $bX;
			});
		} else if ($this->direction->getFloorZ() !== 0) {
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