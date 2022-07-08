<?php

namespace platz1de\EasyEdit\task;

use platz1de\EasyEdit\convert\BlockRotationManipulator;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\session\SessionIdentifier;
use platz1de\EasyEdit\thread\input\TaskInputData;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\thread\output\MessageSendData;
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
	 * @return DynamicStoredRotateTask
	 */
	public static function from(StoredSelectionIdentifier $saveId): DynamicStoredRotateTask
	{
		$instance = new self();
		$instance->saveId = $saveId;
		return $instance;
	}

	/**
	 * @param SessionIdentifier         $owner
	 * @param StoredSelectionIdentifier $id
	 */
	public static function queue(SessionIdentifier $owner, StoredSelectionIdentifier $id): void
	{
		TaskInputData::fromTask($owner, self::from($id));
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "dynamic_storage_rotate";
	}

	public function execute(SessionIdentifier $executor): void
	{
		if (!BlockRotationManipulator::isAvailable()) {
			throw new InternetException("Couldn't load needed data files");
		}
		$start = microtime(true);
		$selection = StorageModule::mustGetDynamic($this->saveId);
		$rotated = new DynamicBlockListSelection($selection->getPlayer(), new Vector3($selection->getPos2()->getZ(), $selection->getPos2()->getY(), $selection->getPos2()->getX()), new Vector3(-$selection->getPos2()->getZ() - $selection->getPoint()->getZ(), $selection->getPoint()->getY(), $selection->getPoint()->getX()));
		$selection->setPoint(Vector3::zero());
		$selection->useOnBlocks(function (int $x, int $y, int $z) use ($selection, $rotated): void {
			$block = $selection->getIterator()->getBlock($x, $y, $z);
			Selection::processBlock($block);
			$rotated->addBlock($selection->getPos2()->getFloorZ() - $z, $y, $x, BlockRotationManipulator::rotate($block));
		}, SelectionContext::full(), $selection);
		foreach ($selection->getTiles() as $tile) {
			$rotated->addTile(TileUtils::rotateCompound($tile, $selection->getPos2()->getFloorZ()));
		}
		StorageModule::forceStore($this->saveId, $rotated);
		MessageSendData::from($executor, Messages::replace("blocks-rotated", ["{time}" => (string) round(microtime(true) - $start, 2), "{changed}" => MixedUtils::humanReadable($rotated->getIterator()->getWrittenBlockCount())]));
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