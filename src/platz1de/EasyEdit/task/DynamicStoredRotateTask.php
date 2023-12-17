<?php

namespace platz1de\EasyEdit\task;

use platz1de\EasyEdit\convert\BlockRotationManipulator;
use platz1de\EasyEdit\math\BlockOffsetVector;
use platz1de\EasyEdit\math\BlockVector;
use platz1de\EasyEdit\result\SelectionManipulationResult;
use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\thread\block\BlockStateTranslationManager;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\TileUtils;
use pocketmine\utils\InternetException;
use RuntimeException;

/**
 * @extends ExecutableTask<SelectionManipulationResult>
 */
class DynamicStoredRotateTask extends ExecutableTask
{
	use EditThreadExclusive;

	/**
	 * @param StoredSelectionIdentifier $saveId
	 */
	public function __construct(private StoredSelectionIdentifier $saveId)
	{
		parent::__construct();
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "dynamic_storage_rotate";
	}

	protected function executeInternal(): SelectionManipulationResult
	{
		if (!BlockRotationManipulator::isAvailable()) {
			throw new InternetException("Couldn't load needed data files");
		}
		$selection = StorageModule::mustGetDynamic($this->saveId);

		$palette = [];
		foreach ($selection->requestBlockStates() as $key => $state) {
			$palette[$key] = BlockRotationManipulator::rotate($state);
		}
		$map = BlockStateTranslationManager::requestRuntimeId($palette);

		$rotated = $selection->createSafeClone();
		$rotated->free();
		$rotated->setPos2(new BlockVector($selection->getPos2()->z, $selection->getPos2()->y, $selection->getPos2()->x));
		$rotated->getManager()->loadBetween($rotated->getPos1(), $rotated->getPos2());
		$rotated->setPoint(new BlockOffsetVector(-$selection->getPos2()->z - $selection->getPoint()->z, $selection->getPoint()->y, $selection->getPoint()->x));
		$selection->setPoint(BlockOffsetVector::zero());
		$dz = $selection->getPos2()->z;
		$constructors = iterator_to_array($selection->asShapeConstructors(function (int $x, int $y, int $z) use ($dz, $selection, $rotated, $map): void {
			$block = $selection->getIterator()->getBlock($x, $y, $z);
			$rotated->addBlock($dz - $z, $y, $x, $map[$block] ?? throw new RuntimeException("Missing block $block"));
		}, SelectionContext::full()));
		//TODO: add possibility to response to requests
		foreach ($selection->getNeededChunks() as $chunk) {
			foreach ($constructors as $constructor) {
				$constructor->moveTo($chunk);
			}
		}
		foreach ($selection->getTiles($selection->getPos1(), $selection->getPos2()) as $tile) {
			$rotated->addTile(TileUtils::rotateCompound($tile, $selection->getPos2()->z));
		}
		StorageModule::forceStore($this->saveId, $rotated);
		return new SelectionManipulationResult($rotated->getIterator()->getWrittenBlockCount());
	}

	public function attemptRecovery(): SelectionManipulationResult
	{
		//TODO: splitting so we can some report time
		return new SelectionManipulationResult(0);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->saveId->fastSerialize());
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->saveId = StoredSelectionIdentifier::fastDeserialize($stream->getString());
	}
}