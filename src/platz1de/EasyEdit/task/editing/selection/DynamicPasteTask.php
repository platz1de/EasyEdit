<?php

namespace platz1de\EasyEdit\task\editing\selection;

use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\selection\StaticBlockListSelection;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\type\PastingNotifier;
use platz1de\EasyEdit\thread\input\TaskInputData;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\TileUtils;
use platz1de\EasyEdit\world\HeightMapCache;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\world\Position;

class DynamicPasteTask extends SelectionEditTask
{
	use PastingNotifier;

	/**
	 * @var DynamicBlockListSelection
	 */
	protected Selection $current;
	/**
	 * @var DynamicBlockListSelection
	 */
	protected Selection $selection;

	protected bool $insert;

	/**
	 * @param string                    $owner
	 * @param string                    $world
	 * @param AdditionalDataManager     $data
	 * @param DynamicBlockListSelection $selection
	 * @param Vector3                   $position
	 * @param Vector3                   $splitOffset
	 * @param bool                      $insert
	 * @return DynamicPasteTask
	 */
	public static function from(string $owner, string $world, AdditionalDataManager $data, DynamicBlockListSelection $selection, Vector3 $position, Vector3 $splitOffset, bool $insert = false): DynamicPasteTask
	{
		$instance = new self($owner, $world, $data, $position);
		SelectionEditTask::initSelection($instance, $selection, $splitOffset);
		$instance->insert = $insert;
		return $instance;
	}

	/**
	 * @param DynamicBlockListSelection $selection
	 * @param Position                  $place
	 * @param bool                      $insert
	 */
	public static function queue(DynamicBlockListSelection $selection, Position $place, bool $insert = false): void
	{
		TaskInputData::fromTask(self::from($selection->getPlayer(), $place->getWorld()->getFolderName(), new AdditionalDataManager(true, true), $selection, $place->asVector3(), $place->asVector3(), $insert));
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "dynamic_paste";
	}

	public function executeEdit(EditTaskHandler $handler): void
	{
		$selection = $this->current;
		$place = $this->getPosition()->addVector($selection->getPoint());
		$ox = $place->getFloorX();
		$oy = $place->getFloorY();
		$oz = $place->getFloorZ();
		if ($this->insert) {
			$ignore = HeightMapCache::getIgnore();
			$selection->useOnBlocks(function (int $x, int $y, int $z) use ($ox, $oy, $oz, $ignore, $handler, $selection): void {
				$block = $selection->getIterator()->getBlock($x - $ox, $y - $oy, $z - $oz);
				if (Selection::processBlock($block) && $block !== 0 && in_array($handler->getBlock($x, $y, $z) >> Block::INTERNAL_METADATA_BITS, $ignore, true)) {
					$handler->changeBlock($x, $y, $z, $block);
				}
			}, SelectionContext::full(), $this->getTotalSelection());
		} else {
			$selection->useOnBlocks(function (int $x, int $y, int $z) use ($ox, $oy, $oz, $handler, $selection): void {
				$block = $selection->getIterator()->getBlock($x - $ox, $y - $oy, $z - $oz);
				if (Selection::processBlock($block)) {
					$handler->changeBlock($x, $y, $z, $block);
				}
			}, SelectionContext::full(), $this->getTotalSelection());
		}

		foreach ($selection->getTiles() as $tile) {
			$handler->addTile(TileUtils::offsetCompound($tile, $place->getFloorX(), $place->getFloorY(), $place->getFloorZ()));
		}
	}

	/**
	 * @return StaticBlockListSelection
	 */
	public function getUndoBlockList(): BlockListSelection
	{
		return new StaticBlockListSelection($this->getOwner(), $this->getWorld(), $this->selection->getPos1()->addVector($this->getPosition())->addVector($this->selection->getPoint()), $this->selection->getPos2()->addVector($this->getPosition())->addVector($this->selection->getPoint()));
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