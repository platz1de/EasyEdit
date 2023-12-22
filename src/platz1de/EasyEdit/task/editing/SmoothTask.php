<?php

namespace platz1de\EasyEdit\task\editing;

use Generator;
use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\result\EditTaskResult;
use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
use platz1de\EasyEdit\selection\identifier\SelectionIdentifier;
use platz1de\EasyEdit\selection\identifier\SelectionSerializer;
use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\selection\StaticBlockListSelection;
use platz1de\EasyEdit\task\CancelException;
use platz1de\EasyEdit\task\EditThreadExclusive;
use platz1de\EasyEdit\task\ExecutableTask;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\world\HeightMapCache;
use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Axis;
use pocketmine\math\Vector3;
use pocketmine\world\World;

/**
 * @extends ExecutableTask<EditTaskResult>
 */
class SmoothTask extends ExecutableTask
{
	use EditThreadExclusive;

	/**
	 * @param SelectionIdentifier $selection
	 */
	public function __construct(private SelectionIdentifier $selection)
	{
		parent::__construct();
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "smooth";
	}

	/**
	 * @return EditTaskResult
	 * @throws CancelException
	 */
	protected function executeInternal(): EditTaskResult
	{
		$selection = $this->selection->asSelection();
		$chunkHandler = new FullChunkHandler($selection->getWorldName());
		EasyEdit::getEnv()->initChunkHandler($chunkHandler);

		$min = $selection->getPos1()->add(-1, 0, -1);
		$max = $selection->getPos2()->add(1, 0, 1);

		for ($x = $min->x; $x <= $max->x; $x++) {
			for ($z = $min->z; $z <= $max->z; $z++) {
				$chunkHandler->request(World::chunkHash($x, $z));
			}
		}

		while (!$chunkHandler->isDone()) {
			EditThread::getInstance()->checkExecution();
			EditThread::getInstance()->waitForData();
		}

		$undo = new StaticBlockListSelection($selection->getWorldName(), $selection->getPos1(), $selection->getPos2());
		$editHandler = new EditTaskHandler($selection->getWorldName(), $undo);
		HeightMapCache::loadBetween($editHandler->getOrigin(), $min, $max);

		$constructors = iterator_to_array($this->prepareConstructors($editHandler));
		foreach ($chunkHandler->getChunkIndexes() as $chunk) {
			foreach ($constructors as $constructor) {
				$constructor->moveTo($chunk);
			}
		}
		return new EditTaskResult($editHandler->getChangedBlockCount(), StorageModule::store($undo));
		return new EditTaskResult($editHandler->getChangedBlockCount(), $undo);
	}

	public function attemptRecovery(): EditTaskResult
	{
		return new EditTaskResult(0, StoredSelectionIdentifier::invalid());
	}

	/**
	 * This is pretty much magic code, so better don't touch it
	 * @param EditTaskHandler $handler
	 * @return Generator<ShapeConstructor>
	 */
	public function prepareConstructors(EditTaskHandler $handler): Generator
	{
		$currentX = null;
		$currentZ = null;
		$map = [];
		$reference = [];
		$air = VanillaBlocks::AIR()->getStateId();
		yield from $this->selection->asSelection()->asShapeConstructors(function (int $x, int $y, int $z) use ($air, &$currentX, &$currentZ, &$map, &$reference, $handler): void {
			if ($currentX !== $x || $currentZ !== $z) {
				//Prepare data sets for all y-values
				$currentX = $x;
				$currentZ = $z;
				$map = $this->modifyDepthMap(HeightMapCache::generateFullDepthMap($x, $z));
				$reference = $map;
				for ($i = -1; $i <= 1; $i++) {
					for ($j = -1; $j <= 1; $j++) {
						if ($i === 0 && $j === 0) {
							continue;
						}
						foreach ($this->modifyDepthMap(HeightMapCache::generateFullDepthMap($x + $i, $z + $j)) as $height => $depth) {
							$reference[$height] += $depth;
						}
					}
				}
				$start = -1;
				foreach ($reference as $height => $depth) {
					if ($depth < -4.5) {
						$reference[$height] = -1;
						if ($start !== -1) {
							for ($i = $start; $i < $height; $i++) {
								$reference[$i] = min($i - $start, $height - $i - 1);
							}
							$start = -1;
						}
						continue;
					}
					if ($start === -1) {
						$start = $height;
					}
				}
				if ($start !== -1) {
					for ($i = $start; $i < World::Y_MAX; $i++) {
						$reference[$i] = min($i - $start, World::Y_MAX - $i - 1);
					}
				}
			}
			if ($map[$y] === $reference[$y]) {
				return;
			}
			if ($reference[$y] <= 0) {
				if ($y < (World::Y_MAX - 1)) {
					$b = $handler->getBlock($x, $y + 1, $z);
					if ($b >> Block::INTERNAL_STATE_DATA_BITS === BlockTypeIds::WATER || $b >> Block::INTERNAL_STATE_DATA_BITS === BlockTypeIds::LAVA) {
						$handler->changeBlock($x, $y, $z, $b);
						return;
					}
				}
				for ($i = -1; $i <= 1; $i++) {
					for ($j = -1; $j <= 1; $j++) {
						if ($i === 0 && $j === 0) {
							continue;
						}
						$b = $handler->getBlock($x + $i, $y, $z + $j);
						if ($b >> Block::INTERNAL_STATE_DATA_BITS === BlockTypeIds::WATER || $b >> Block::INTERNAL_STATE_DATA_BITS === BlockTypeIds::LAVA) {
							$handler->changeBlock($x, $y, $z, $b);
							return;
						}
					}
				}
				$handler->changeBlock($x, $y, $z, $air);
				return;
			}

			$multiplier = ($reference[$y] < $reference[$y + 1]) ? 1 : -1;

			for ($i = 0; $i < 3; $i++) { //search 2 blocks up/downwards
				if ($map[$y + $multiplier * $i] !== $air) {
					break;
				}
			}
			if (($i ?? 0) === 3) {
				//no blocks found, setting from neighbours
				foreach ((new Vector3($x, $y, $z))->sidesAroundAxis(Axis::Y) as $side) {
					if (($block = $handler->getBlock($side->getFloorX(), $side->getFloorY(), $side->getFloorZ())) !== $air) {
						$handler->changeBlock($x, $y, $z, $block);
						return;
					}
				}
				return;
			}
			$i = 0;
			while ($i < ($multiplier === 1 ? World::Y_MAX : $y)) {
				if ($map[$y + $multiplier * ($i + 1)] <= $map[$y + $multiplier * $i]) {
					break;
				}
				$i++;
			}
			//original blocks
			$oMax = $y + $multiplier * $i;
			$oMin = $y + $multiplier * ($i - $map[$y + $multiplier * $i] + 1);

			$i = 0;
			while ($i < ($multiplier === 1 ? World::Y_MAX - $y - 1 : $y)) {
				if ($reference[$y + $multiplier * ($i + 1)] <= $reference[$y + $multiplier * $i]) {
					break;
				}
				$i++;
			}
			$nMax = $y + $multiplier * $i;
			$nMin = $y + $multiplier * ($i - $reference[$y + $multiplier * $i] + 1);

			$anchor = $multiplier === 1 ? max($nMax, $oMax) : min($nMax, $oMax);

			$position = ($anchor - $y + $multiplier * 1) / ($anchor - $nMin + $multiplier * 1);
			//absolutely no idea why this can exceed world limitations, but it does?
			$target = (int) min(World::Y_MAX - 1, max(World::Y_MIN, round($anchor + $multiplier * $position * ($anchor - $oMin))));

			if ($map[$target] < 1) {
				return; //avoid populating with air due to merging anchor points
			}

			$handler->copyBlock($x, $y, $z, $x, $target, $z);
		}, SelectionContext::full());
	}

	/**
	 * @param int[] $map
	 * @return int[]
	 */
	private function modifyDepthMap(array $map): array
	{
		$start = -1;
		foreach ($map as $y => $depth) {
			if ($depth !== 0) {
				if ($start !== -1) {
					for ($i = $start; $i < $y; $i++) {
						$map[$i] = (int) max(-1, $start - $i, $i - $y + 1);
					}
				}
				$start = -1;
				continue;
			}
			if ($start === -1) {
				$start = $y;
			}
		}
		if ($start !== -1) {
			for ($i = $start; $i < World::Y_MAX; $i++) {
				$map[$i] = (int) max(-1, $start - $i);
			}
		}
		return $map;
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString(SelectionSerializer::fastSerialize($this->selection));
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->selection = SelectionSerializer::fastDeserialize($stream->getString());
	}
}