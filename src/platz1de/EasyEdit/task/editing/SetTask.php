<?php

namespace platz1de\EasyEdit\task\editing;

use Generator;
use platz1de\EasyEdit\pattern\functional\GravityPattern;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\pattern\PatternWrapper;
use platz1de\EasyEdit\selection\BinaryBlockListStream;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
use platz1de\EasyEdit\selection\LinearSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\selection\VerticalStaticBlockListSelection;
use platz1de\EasyEdit\task\editing\cubic\CubicStaticUndo;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\world\HeightMapCache;
use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;

class SetTask extends SelectionEditTask
{
	use CubicStaticUndo {
		CubicStaticUndo::createUndoBlockList as private getDefaultBlockList;
	}

	protected Pattern $pattern;

	/**
	 * @param Selection             $selection
	 * @param Pattern               $pattern
	 * @param SelectionContext|null $context
	 */
	public function __construct(Selection $selection, Pattern $pattern, ?SelectionContext $context = null)
	{
		$pattern = PatternWrapper::wrap([$pattern]);
		$this->pattern = $pattern;
		if ($context === null) {
			$context = $pattern->getSelectionContext();
		}
		parent::__construct($selection, $context);
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "set";
	}

	/**
	 * @param EditTaskHandler $handler
	 * @return Generator<ShapeConstructor>
	 */
	public function prepareConstructors(EditTaskHandler $handler): Generator
	{
		$selection = $this->selection;
		$pattern = $this->pattern;
		$updateHeightMap = $pattern->contains(GravityPattern::class);
		yield from $selection->asShapeConstructors(function (int $x, int $y, int $z) use ($updateHeightMap, $pattern, $selection, $handler): void {
			$block = $pattern->getFor($x, $y, $z, $handler->getOrigin(), $selection);
			if ($block !== -1) {
				$handler->changeBlock($x, $y, $z, $block);
				if ($updateHeightMap) {
					HeightMapCache::setBlockAt($x, $y, $z, $block >> Block::INTERNAL_STATE_DATA_BITS === BlockTypeIds::AIR);
				}
			}
		}, $this->context);
	}

	/**
	 * @return BlockListSelection
	 */
	public function createUndoBlockList(): BlockListSelection
	{
		if ($this->selection instanceof LinearSelection) {
			return new BinaryBlockListStream($this->getWorld());
		}
		if ($this->pattern->contains(GravityPattern::class)) {
			return new VerticalStaticBlockListSelection($this->getWorld(), $this->getSelection()->getPos1(), $this->getSelection()->getPos2());
		}
		return $this->getDefaultBlockList();
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);
		$stream->putString($this->pattern->fastSerialize());
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);
		$this->pattern = Pattern::fastDeserialize($stream->getString());
	}
}