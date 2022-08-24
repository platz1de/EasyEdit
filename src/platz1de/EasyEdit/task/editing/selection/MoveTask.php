<?php

namespace platz1de\EasyEdit\task\editing\selection;

use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\selection\StaticBlockListSelection;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\type\SettingNotifier;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class MoveTask extends SelectionEditTask
{
	use SettingNotifier;

	private Vector3 $direction;

	/**
	 * @param Selection $selection
	 * @param Vector3   $direction
	 */
	public function __construct(Selection $selection, Vector3 $direction)
	{
		parent::__construct($selection);
		$this->direction = $direction;
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "move";
	}

	public function executeEdit(EditTaskHandler $handler, Vector3 $min, Vector3 $max): void
	{
		$selection = $this->selection;
		$direction = $this->direction;
		$dMin = $min->addVector($direction);
		$dMax = $max->addVector($direction);
		$chunks = [];
		for ($x = $dMin->getX() >> 4; $x <= $dMax->getX() >> 4; $x++) {
			for ($z = $dMin->getZ() >> 4; $z <= $dMax->getZ() >> 4; $z++) {
				$chunks[] = World::chunkHash($x, $z);
			}
		}
		$this->requestRuntimeChunks($handler, $chunks);
		$handler->getChanges()->checkCachedData();
		//TODO: change order of iteration to optimize performance
		$selection->useOnBlocks(function (int $x, int $y, int $z) use ($handler): void {
			$handler->changeBlock($x, $y, $z, 0); //Make sure we don't overwrite anything
		}, SelectionContext::full(), $min, $max);
		$selection->useOnBlocks(function (int $x, int $y, int $z) use ($handler, $direction): void {
			$handler->copyBlock($x + $direction->getFloorX(), $y + $direction->getFloorY(), $z + $direction->getFloorZ(), $x, $y, $z, false);
		}, SelectionContext::full(), $min, $max);
	}

	protected function orderChunks(array $chunks): array
	{
		if ($this->direction->getFloorX() === 0 && $this->direction->getFloorZ() !== 0) {
			$z = array_map(static function (int $c): int {
				World::getXZ($c, $x, $z);
				return $z;
			}, $chunks);
			array_multisort($z, $this->direction->getFloorZ() > 0 ? SORT_DESC : SORT_ASC, $chunks);
			return $chunks;
		}
		$x = array_map(static function (int $c): int {
			World::getXZ($c, $x, $z);
			return $x;
		}, $chunks);
		$z = array_map(static function (int $c): int {
			World::getXZ($c, $x, $z);
			return $z;
		}, $chunks);
		array_multisort($x, $this->direction->getFloorX() > 0 ? SORT_DESC : SORT_ASC, $z, $this->direction->getFloorZ() > 0 ? SORT_DESC : SORT_ASC, $chunks);
		return $chunks;
	}

	/**
	 * @return BlockListSelection
	 */
	public function getUndoBlockList(): BlockListSelection
	{
		return new StaticBlockListSelection($this->getWorld(), Vector3::minComponents($this->getSelection()->getCubicStart(), $this->getSelection()->getCubicStart()->addVector($this->direction)), Vector3::maxComponents($this->getSelection()->getCubicEnd(), $this->getSelection()->getCubicEnd()->addVector($this->direction)));
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);
		$stream->putVector($this->direction);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);
		$this->direction = $stream->getVector();
	}
}