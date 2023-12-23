<?php

namespace platz1de\EasyEdit\task;

use platz1de\EasyEdit\convert\BlockRotationManipulator;
use platz1de\EasyEdit\math\BlockOffsetVector;
use platz1de\EasyEdit\result\SelectionManipulationResult;
use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\thread\block\BlockStateTranslationManager;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\TileUtils;
use pocketmine\math\Axis;
use pocketmine\utils\InternetException;
use UnexpectedValueException;

/**
 * @extends ExecutableTask<SelectionManipulationResult>
 */
class DynamicStoredFlipTask extends ExecutableTask
{
	use EditThreadExclusive;

	/**
	 * @param StoredSelectionIdentifier $saveId
	 * @param int                       $axis
	 */
	public function __construct(private StoredSelectionIdentifier $saveId, private int $axis)
	{
		parent::__construct();
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "dynamic_storage_flip";
	}

	protected function executeInternal(): SelectionManipulationResult
	{
		if (!BlockRotationManipulator::isAvailable()) {
			throw new InternetException("Couldn't load needed data files");
		}
		$selection = StorageModule::mustGetDynamic($this->saveId);

		$palette = [];
		foreach ($selection->requestBlockStates() as $key => $state) {
			$palette[$key] = BlockRotationManipulator::flip($this->axis, $state);
		}
		$map = BlockStateTranslationManager::requestRuntimeId($palette);

		//TODO: Add a less wasteful way to copy a selection properly
		$flipped = $selection->createSafeClone();
		$flipped->free();
		$flipped->getManager()->loadBetween($flipped->getPos1(), $flipped->getPos2());
		$flipped->setPoint($selection->getPoint()->setComponent($this->axis, -$selection->getPos2()->getComponent($this->axis) - $selection->getPoint()->getComponent($this->axis)));
		$selection->setPoint(BlockOffsetVector::zero());
		$dx = $selection->getPos2()->x;
		$dy = $selection->getPos2()->y;
		$dz = $selection->getPos2()->z;
		$constructors = iterator_to_array(match ($this->axis) {
			Axis::X => $selection->asShapeConstructors(function (int $x, int $y, int $z) use ($dx, $selection, $flipped, $map): void {
				$block = $selection->getIterator()->getBlock($x, $y, $z);
				$flipped->addBlock($dx - $x, $y, $z, $map[$block] ?? throw new UnexpectedValueException("Unknown block $block"));
			}, SelectionContext::full()),
			Axis::Y => $selection->asShapeConstructors(function (int $x, int $y, int $z) use ($dy, $selection, $flipped, $map): void {
				$block = $selection->getIterator()->getBlock($x, $y, $z);
				$flipped->addBlock($x, $dy - $y, $z, $map[$block] ?? throw new UnexpectedValueException("Unknown block $block"));
			}, SelectionContext::full()),
			Axis::Z => $selection->asShapeConstructors(function (int $x, int $y, int $z) use ($dz, $selection, $flipped, $map): void {
				$block = $selection->getIterator()->getBlock($x, $y, $z);
				$flipped->addBlock($x, $y, $dz - $z, $map[$block] ?? throw new UnexpectedValueException("Unknown block $block"));
			}, SelectionContext::full()),
			default => throw new UnexpectedValueException("Invalid axis " . $this->axis)
		});
		//TODO: add possibility to response to requests
		foreach ($selection->getNeededChunks() as $chunk) {
			foreach ($constructors as $constructor) {
				$constructor->moveTo($chunk);
			}
		}
		foreach ($selection->getTiles($selection->getPos1(), $selection->getPos2()) as $tile) {
			$flipped->addTile(TileUtils::flipCompound($this->axis, $tile, $selection->getPos2()->getComponent($this->axis)));
		}
		if ($this->shouldWriteInPlace()) {
			StorageModule::forceStore($this->saveId, $flipped);
			return new SelectionManipulationResult($flipped->getIterator()->getWrittenBlockCount(), $this->saveId);
		}
		return new SelectionManipulationResult($flipped->getIterator()->getWrittenBlockCount(), $flipped);
	}

	public function attemptRecovery(): SelectionManipulationResult
	{
		//TODO: splitting
		return new SelectionManipulationResult(0, StoredSelectionIdentifier::invalid());
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->saveId->fastSerialize());
		$stream->putInt($this->axis);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->saveId = StoredSelectionIdentifier::fastDeserialize($stream->getString());
		$this->axis = $stream->getInt();
	}
}