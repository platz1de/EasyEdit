<?php

namespace platz1de\EasyEdit\task\editing\selection;

use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\selection\StackedCube;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\selection\cubic\CubicStaticUndo;
use platz1de\EasyEdit\task\editing\type\SettingNotifier;
use platz1de\EasyEdit\thread\input\TaskInputData;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\world\HeightMapCache;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\world\Position;

class StackTask extends SelectionEditTask
{
	use CubicStaticUndo;
	use SettingNotifier;

	/**
	 * @var StackedCube
	 */
	protected Selection $current;

	private bool $insert;

	/**
	 * @param string                $owner
	 * @param string                $world
	 * @param AdditionalDataManager $data
	 * @param Selection             $selection
	 * @param Vector3               $position
	 * @param Vector3               $splitOffset
	 * @param bool                  $insert
	 * @return StackTask
	 */
	public static function from(string $owner, string $world, AdditionalDataManager $data, Selection $selection, Vector3 $position, Vector3 $splitOffset, bool $insert = false): StackTask
	{
		$instance = new self($owner, $world, $data, $position);
		SelectionEditTask::initSelection($instance, $selection, $splitOffset);
		$instance->insert = $insert;
		return $instance;
	}

	/**
	 * @param StackedCube $selection
	 * @param Position    $place
	 * @param bool        $insert
	 */
	public static function queue(StackedCube $selection, Position $place, bool $insert = false): void
	{
		TaskInputData::fromTask(self::from($selection->getPlayer(), $selection->getWorldName(), new AdditionalDataManager(true, true), $selection, $place->asVector3(), Vector3::zero(), $insert));
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "stack";
	}

	public function executeEdit(EditTaskHandler $handler): void
	{
		$selection = $this->current;
		if ($selection->isCopyMode()) {
			$offset = $selection->getCopyOffset();
			if ($this->insert) {
				$ignore = HeightMapCache::getIgnore();
				$selection->useOnBlocks(function (int $x, int $y, int $z) use ($offset, $ignore, $handler): void {
					$block = $handler->getBlock($offset->getFloorX() + $x, $offset->getFloorY() + $y, $offset->getFloorZ() + $z);
					if ($block !== 0 && in_array($handler->getBlock($x, $y, $z) >> Block::INTERNAL_METADATA_BITS, $ignore, true)) {
						$handler->changeBlock($x, $y, $z, $block);
					}
				}, SelectionContext::full(), $this->getTotalSelection());
			} else {
				$selection->useOnBlocks(function (int $x, int $y, int $z) use ($offset, $handler): void {
					$handler->copyBlock($x, $y, $z, $offset->getFloorX() + $x, $offset->getFloorY() + $y, $offset->getFloorZ() + $z);
				}, SelectionContext::full(), $this->getTotalSelection());
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
			}, SelectionContext::full(), $this->getTotalSelection());
		} else {
			$selection->useOnBlocks(function (int $x, int $y, int $z) use ($handler, $originalSize, $start): void {
				$handler->copyBlock($x, $y, $z, $start->getFloorX() + (($x - $start->getX()) % $originalSize->getX() + $originalSize->getX()) % $originalSize->getX(), $start->getFloorY() + (($y - $start->getY()) % $originalSize->getY() + $originalSize->getY()) % $originalSize->getY(), $start->getFloorZ() + (($z - $start->getZ()) % $originalSize->getZ() + $originalSize->getZ()) % $originalSize->getZ());
			}, SelectionContext::full(), $this->getTotalSelection());
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