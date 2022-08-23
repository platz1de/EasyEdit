<?php

namespace platz1de\EasyEdit\task;

use platz1de\EasyEdit\convert\BlockRotationManipulator;
use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\thread\output\session\MessageSendData;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\MixedUtils;
use platz1de\EasyEdit\utils\TileUtils;
use pocketmine\math\Vector3;
use pocketmine\utils\InternetException;

class DynamicStoredRotateTask extends ExecutableTask
{
	private StoredSelectionIdentifier $saveId;

	/**
	 * @param StoredSelectionIdentifier $saveId
	 */
	public function __construct(StoredSelectionIdentifier $saveId)
	{
		$this->saveId = $saveId;
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
		$rotated = new DynamicBlockListSelection(new Vector3($selection->getPos2()->getZ(), $selection->getPos2()->getY(), $selection->getPos2()->getX()), $selection->getWorldOffset(), new Vector3(-$selection->getPos2()->getZ() - $selection->getPoint()->getZ(), $selection->getPoint()->getY(), $selection->getPoint()->getX()));
		$selection->setPoint(Vector3::zero());
		$selection->useOnBlocks(function (int $x, int $y, int $z) use ($selection, $rotated): void {
			$block = $selection->getIterator()->getBlock($x, $y, $z);
			Selection::processBlock($block);
			$rotated->addBlock($selection->getPos2()->getFloorZ() - $z, $y, $x, BlockRotationManipulator::rotate($block));
		}, SelectionContext::full(), Vector3::zero(), $selection->getPos2());
		foreach ($selection->getTiles() as $tile) {
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