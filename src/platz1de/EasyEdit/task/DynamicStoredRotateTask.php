<?php

namespace platz1de\EasyEdit\task;

use platz1de\EasyEdit\convert\BlockRotationManipulator;
use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\thread\block\BlockStateTranslationManager;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\thread\output\session\MessageSendData;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\MixedUtils;
use platz1de\EasyEdit\utils\TileUtils;
use pocketmine\math\Vector3;
use pocketmine\utils\InternetException;
use RuntimeException;

class DynamicStoredRotateTask extends ExecutableTask
{
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

	public function execute(): void
	{
		if (!BlockRotationManipulator::isAvailable()) {
			throw new InternetException("Couldn't load needed data files");
		}
		$start = microtime(true);
		$selection = StorageModule::mustGetDynamic($this->saveId);

		$palette = $selection->requestBlockStates();
		if ($palette === false) {
			return;
		}
		foreach ($palette as $key => $state) {
			$palette[$key] = BlockRotationManipulator::rotate($state);
		}
		$map = BlockStateTranslationManager::requestRuntimeId($palette);
		if ($map === false) {
			return;
		}

		$rotated = new DynamicBlockListSelection(new Vector3($selection->getPos2()->getZ(), $selection->getPos2()->getY(), $selection->getPos2()->getX()), $selection->getWorldOffset(), new Vector3(-$selection->getPos2()->getZ() - $selection->getPoint()->getZ(), $selection->getPoint()->getY(), $selection->getPoint()->getX()));
		$selection->setPoint(Vector3::zero());
		$selection->asShapeConstructors(function (int $x, int $y, int $z) use ($selection, $rotated, $map): void {
			$block = $selection->getIterator()->getBlock($x, $y, $z);
			$rotated->addBlock($selection->getPos2()->getFloorZ() - $z, $y, $x, $map[$block] ?? throw new RuntimeException("Missing block $block"));
		}, SelectionContext::full());
		foreach ($selection->getTiles($selection->getPos1(), $selection->getPos2()) as $tile) {
			$rotated->addTile(TileUtils::rotateCompound($tile, $selection->getPos2()->getFloorZ()));
		}
		StorageModule::forceStore($this->saveId, $rotated);
		$this->sendOutputPacket(new MessageSendData("blocks-rotated", ["{time}" => (string) round(microtime(true) - $start, 2), "{changed}" => MixedUtils::humanReadable($rotated->getIterator()->getWrittenBlockCount())]));
	}

	public function getProgress(): float
	{
		return 0; //No splitting
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