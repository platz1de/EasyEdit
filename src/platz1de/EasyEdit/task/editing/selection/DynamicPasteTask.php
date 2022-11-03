<?php

namespace platz1de\EasyEdit\task\editing\selection;

use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\StaticBlockListSelection;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\type\PastingNotifier;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\TileUtils;
use platz1de\EasyEdit\world\HeightMapCache;
use pocketmine\block\Block;
use pocketmine\math\Vector3;

class DynamicPasteTask extends SelectionEditTask
{
	use PastingNotifier;

	public const MODE_REPLACE_ALL = 0; //Replace everything with the selection
	public const MODE_REPLACE_AIR = 1; //Replace air with the selection
	public const MODE_ONLY_SOLID = 2; //Only paste solid blocks
	public const MODE_REPLACE_SOLID = 3; //Replace solid blocks with solid blocks from the selection

	/**
	 * @var DynamicBlockListSelection
	 */
	protected Selection $selection;

	protected Vector3 $position;
	protected int $mode;

	/**
	 * @param string                    $world
	 * @param DynamicBlockListSelection $selection
	 * @param Vector3                   $position
	 * @param int                       $mode
	 */
	public function __construct(string $world, DynamicBlockListSelection $selection, Vector3 $position, int $mode = self::MODE_REPLACE_ALL)
	{
		$this->mode = $mode;
		$this->position = $position;
		$selection->setPoint($selection->getPoint()->addVector($position));
		parent::__construct($selection);
		$this->world = $world;
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "dynamic_paste";
	}

	public function executeEdit(EditTaskHandler $handler, Vector3 $min, Vector3 $max): void
	{
		$selection = $this->selection;
		$place = $selection->getPoint();
		$ox = $place->getFloorX();
		$oy = $place->getFloorY();
		$oz = $place->getFloorZ();
		switch ($this->mode) {
			case self::MODE_REPLACE_ALL:
				$selection->useOnBlocks(function (int $x, int $y, int $z) use ($ox, $oy, $oz, $handler, $selection): void {
					$block = $selection->getIterator()->getBlock($x - $ox, $y - $oy, $z - $oz);
					if (Selection::processBlock($block)) {
						$handler->changeBlock($x, $y, $z, $block);
					}
				}, $this->context, $min, $max);
				break;
			case self::MODE_REPLACE_AIR:
				$ignore = HeightMapCache::getIgnore();
				$selection->useOnBlocks(function (int $x, int $y, int $z) use ($ox, $oy, $oz, $ignore, $handler, $selection): void {
					$block = $selection->getIterator()->getBlock($x - $ox, $y - $oy, $z - $oz);
					if (Selection::processBlock($block) && $block !== 0 && in_array($handler->getBlock($x, $y, $z) >> Block::INTERNAL_METADATA_BITS, $ignore, true)) {
						$handler->changeBlock($x, $y, $z, $block);
					}
				}, $this->context, $min, $max);
				break;
			case self::MODE_ONLY_SOLID:
				$selection->useOnBlocks(function (int $x, int $y, int $z) use ($ox, $oy, $oz, $handler, $selection): void {
					$block = $selection->getIterator()->getBlock($x - $ox, $y - $oy, $z - $oz);
					if (Selection::processBlock($block) && $block !== 0) {
						$handler->changeBlock($x, $y, $z, $block);
					}
				}, $this->context, $min, $max);
				break;
			case self::MODE_REPLACE_SOLID:
				$ignore = HeightMapCache::getIgnore();
				$selection->useOnBlocks(function (int $x, int $y, int $z) use ($ox, $oy, $oz, $ignore, $handler, $selection): void {
					$block = $selection->getIterator()->getBlock($x - $ox, $y - $oy, $z - $oz);
					if (Selection::processBlock($block) && $block !== 0 && !in_array($handler->getBlock($x, $y, $z) >> Block::INTERNAL_METADATA_BITS, $ignore, true)) {
						$handler->changeBlock($x, $y, $z, $block);
					}
				}, $this->context, $min, $max);
		}

		foreach ($selection->getOffsetTiles($min, $max) as $tile) {
			$handler->addTile(TileUtils::offsetCompound($tile, $place->getFloorX(), $place->getFloorY(), $place->getFloorZ()));
		}
	}

	/**
	 * @return BlockListSelection
	 */
	public function getUndoBlockList(): BlockListSelection
	{
		return new StaticBlockListSelection($this->getWorld(), $this->selection->getPos1()->addVector($this->selection->getPoint()), $this->selection->getPos2()->addVector($this->selection->getPoint()));
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);
		$stream->putInt($this->mode);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);
		$this->mode = $stream->getInt();
	}
}