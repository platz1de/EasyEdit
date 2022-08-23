<?php

namespace platz1de\EasyEdit\task\editing\selection;

use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\selection\StackedCube;
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

	/**
	 * @var StackedCube
	 */
	protected Selection $selection;

	private bool $insert;

	/**
	 * @param StackedCube $selection
	 * @param bool        $insert
	 */
	public function __construct(StackedCube $selection, bool $insert = false)
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
		//TODO
		return;
		$selection = $this->selection;
		if ($selection->isCopyMode()) {
			$offset = $selection->getCopyOffset();
			if ($this->insert) {
				$ignore = HeightMapCache::getIgnore();
				$selection->useOnBlocks(function (int $x, int $y, int $z) use ($offset, $ignore, $handler): void {
					$block = $handler->getBlock($offset->getFloorX() + $x, $offset->getFloorY() + $y, $offset->getFloorZ() + $z);
					if ($block !== 0 && in_array($handler->getBlock($x, $y, $z) >> Block::INTERNAL_METADATA_BITS, $ignore, true)) {
						$handler->changeBlock($x, $y, $z, $block);
					}
				}, SelectionContext::full(), $this->getTotalSelection(), $min, $max);
			} else {
				$selection->useOnBlocks(function (int $x, int $y, int $z) use ($offset, $handler): void {
					$handler->copyBlock($x, $y, $z, $offset->getFloorX() + $x, $offset->getFloorY() + $y, $offset->getFloorZ() + $z);
				}, SelectionContext::full(), $this->getTotalSelection(), $min, $max);
			}
			return;
		}
		$originalSize = $selection->getPos2()->subtractVector($selection->getPos1())->add(1, 1, 1);
		$start = $selection->getPos1();
		if ($this->insert) {
			$ignore = HeightMapCache::getIgnore();
			$selection->useOnBlocks(function (int $x, int $y, int $z) use ($ignore, $handler, $originalSize, $start): void {
				$block = $handler->getBlock($start->getFloorX() + (($x - $start->getX()) % $originalSize->getX() + $originalSize->getX()) % $originalSize->getX(), $start->getFloorY() + (($y - $start->getY()) % $originalSize->getY() + $originalSize->getY()) % $originalSize->getY(), $start->getFloorZ() + (($z - $start->getZ()) % $originalSize->getZ() + $originalSize->getZ()) % $originalSize->getZ());
				if ($block !== 0 && in_array($handler->getBlock($x, $y, $z) >> Block::INTERNAL_METADATA_BITS, $ignore, true)) {
					$handler->changeBlock($x, $y, $z, $block);
				}
			}, SelectionContext::full(), $this->getTotalSelection(), $min, $max);
		} else {
			$selection->useOnBlocks(function (int $x, int $y, int $z) use ($handler, $originalSize, $start): void {
				$handler->copyBlock($x, $y, $z, $start->getFloorX() + (($x - $start->getX()) % $originalSize->getX() + $originalSize->getX()) % $originalSize->getX(), $start->getFloorY() + (($y - $start->getY()) % $originalSize->getY() + $originalSize->getY()) % $originalSize->getY(), $start->getFloorZ() + (($z - $start->getZ()) % $originalSize->getZ() + $originalSize->getZ()) % $originalSize->getZ());
			}, SelectionContext::full(), $this->getTotalSelection(), $min, $max);
		}
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);
		$stream->putBool($this->insert);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);
		$this->insert = $stream->getBool();
	}
}