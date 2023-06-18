<?php

namespace platz1de\EasyEdit\task\editing;

use Generator;
use InvalidArgumentException;
use platz1de\EasyEdit\math\OffGridBlockVector;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use platz1de\EasyEdit\selection\identifier\BlockListSelectionIdentifier;
use platz1de\EasyEdit\selection\StaticBlockListSelection;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\TileUtils;
use platz1de\EasyEdit\world\HeightMapCache;
use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;

class DynamicPasteTask extends SelectionEditTask
{
	public const MODE_REPLACE_ALL = 0; //Replace everything with the selection
	public const MODE_REPLACE_AIR = 1; //Replace air with the selection
	public const MODE_ONLY_SOLID = 2; //Only paste solid blocks
	public const MODE_REPLACE_SOLID = 3; //Replace solid blocks with solid blocks from the selection

	/**
	 * @param string                       $world
	 * @param BlockListSelectionIdentifier $selection
	 * @param OffGridBlockVector           $position
	 * @param int                          $mode
	 */
	public function __construct(private string $world, BlockListSelectionIdentifier $selection, OffGridBlockVector $position, private int $mode = self::MODE_REPLACE_ALL)
	{
		$selection = StorageModule::mustGetDynamic($selection);
		$selection->setPoint($position->offset($selection->getPoint())->diff(OffGridBlockVector::zero()));
		parent::__construct($selection);
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "dynamic_paste";
	}

	/**
	 * @return DynamicBlockListSelection
	 */
	public function getSelection(): DynamicBlockListSelection
	{
		$sel = parent::getSelection();
		if (!$sel instanceof DynamicBlockListSelection) {
			throw new InvalidArgumentException("Selection must be a dynamic block list");
		}
		return $sel;
	}

	/**
	 * @param EditTaskHandler $handler
	 * @param int             $chunk
	 */
	public function executeEdit(EditTaskHandler $handler, int $chunk): void
	{
		parent::executeEdit($handler, $chunk);

		$place = $this->getSelection()->getPoint();
		foreach ($this->getSelection()->getOffsetTiles($chunk) as $tile) {
			$handler->addTile(TileUtils::offsetCompound($tile, $place->x, $place->y, $place->z));
		}
	}

	/**
	 * @param EditTaskHandler $handler
	 * @return Generator<ShapeConstructor>
	 */
	public function prepareConstructors(EditTaskHandler $handler): Generator
	{
		$selection = $this->getSelection();
		$place = $selection->getPoint();
		$ox = $place->x;
		$oy = $place->y;
		$oz = $place->z;
		$ignore = HeightMapCache::getIgnore();
		yield from match ($this->mode) {
			self::MODE_REPLACE_ALL => $selection->asShapeConstructors(function (int $x, int $y, int $z) use ($ox, $oy, $oz, $handler, $selection): void {
				$block = $selection->getIterator()->getBlock($x - $ox, $y - $oy, $z - $oz);
				if ($block !== 0) {
					$handler->changeBlock($x, $y, $z, $block);
				}
			}, $this->context),
			self::MODE_REPLACE_AIR => $selection->asShapeConstructors(function (int $x, int $y, int $z) use ($ox, $oy, $oz, $ignore, $handler, $selection): void {
				$block = $selection->getIterator()->getBlock($x - $ox, $y - $oy, $z - $oz);
				if ($block !== 0 && $block >> Block::INTERNAL_STATE_DATA_BITS !== BlockTypeIds::AIR && in_array($handler->getBlock($x, $y, $z) >> Block::INTERNAL_STATE_DATA_BITS, $ignore, true)) {
					$handler->changeBlock($x, $y, $z, $block);
				}
			}, $this->context),
			self::MODE_ONLY_SOLID => $selection->asShapeConstructors(function (int $x, int $y, int $z) use ($ox, $oy, $oz, $ignore, $handler, $selection): void {
				$block = $selection->getIterator()->getBlock($x - $ox, $y - $oy, $z - $oz);
				if ($block !== 0 && !in_array($block >> Block::INTERNAL_STATE_DATA_BITS, $ignore, true)) {
					$handler->changeBlock($x, $y, $z, $block);
				}
			}, $this->context),
			self::MODE_REPLACE_SOLID => $selection->asShapeConstructors(function (int $x, int $y, int $z) use ($ox, $oy, $oz, $ignore, $handler, $selection): void {
				$block = $selection->getIterator()->getBlock($x - $ox, $y - $oy, $z - $oz);
				if ($block !== 0 && !in_array($block >> Block::INTERNAL_STATE_DATA_BITS, $ignore, true) && !in_array($handler->getBlock($x, $y, $z) >> Block::INTERNAL_STATE_DATA_BITS, $ignore, true)) {
					$handler->changeBlock($x, $y, $z, $block);
				}
			}, $this->context),
			default => throw new InvalidArgumentException("Invalid mode $this->mode"),
		};
	}

	/**
	 * @return BlockListSelection
	 */
	public function createUndoBlockList(): BlockListSelection
	{
		return new StaticBlockListSelection($this->getTargetWorld(), $this->getSelection()->getPos1()->offset($this->getSelection()->getPoint()), $this->getSelection()->getPos2()->offset($this->getSelection()->getPoint()));
	}

	public function getTargetWorld(): string
	{
		return $this->world;
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);
		$stream->putInt($this->mode);
		$stream->putString($this->world);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);
		$this->mode = $stream->getInt();
		$this->world = $stream->getString();
	}
}