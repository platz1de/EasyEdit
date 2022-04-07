<?php

namespace platz1de\EasyEdit\task;

use platz1de\EasyEdit\convert\BlockRotationManipulator;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\thread\input\TaskInputData;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\thread\output\MessageSendData;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\MixedUtils;
use platz1de\EasyEdit\utils\TileUtils;
use pocketmine\math\Axis;
use pocketmine\math\Vector3;
use pocketmine\utils\InternetException;
use UnexpectedValueException;

class DynamicStoredFlipTask extends ExecutableTask
{
	private StoredSelectionIdentifier $saveId;
	private int $axis;

	/**
	 * @param string                    $owner
	 * @param StoredSelectionIdentifier $saveId
	 * @param int                       $axis
	 * @return DynamicStoredFlipTask
	 */
	public static function from(string $owner, StoredSelectionIdentifier $saveId, int $axis): DynamicStoredFlipTask
	{
		$instance = new self($owner);
		$instance->saveId = $saveId;
		$instance->axis = $axis;
		return $instance;
	}

	/**
	 * @param string                    $owner
	 * @param StoredSelectionIdentifier $id
	 * @param int                       $axis
	 */
	public static function queue(string $owner, StoredSelectionIdentifier $id, int $axis): void
	{
		TaskInputData::fromTask(self::from($owner, $id, $axis));
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "dynamic_storage_flip";
	}

	public function execute(): void
	{
		if (!BlockRotationManipulator::isAvailable()) {
			throw new InternetException("Couldn't load needed data files");
		}
		$start = microtime(true);
		$selection = StorageModule::mustGetDynamic($this->saveId);
		$flipped = new DynamicBlockListSelection($selection->getPlayer(), new Vector3($selection->getPos2()->getX(), $selection->getPos2()->getY(), $selection->getPos2()->getZ()), Vector3::zero());
		switch ($this->axis) {
			case Axis::X:
				$flipped->setPoint(new Vector3(-$selection->getPos2()->getX() - $selection->getPoint()->getX(), $selection->getPoint()->getY(), $selection->getPoint()->getZ()));
				$selection->setPoint(Vector3::zero());
				$selection->useOnBlocks(function (int $x, int $y, int $z) use ($selection, $flipped): void {
					$block = $selection->getIterator()->getBlockAt($x, $y, $z);
					Selection::processBlock($block);
					$flipped->addBlock($selection->getPos2()->getFloorX() - $x, $y, $z, BlockRotationManipulator::flip(Axis::X, $block));
				}, SelectionContext::full(), $selection);
				foreach ($selection->getTiles() as $tile) {
					$flipped->addTile(TileUtils::flipCompound(Axis::X, $tile, $selection->getPos2()->getFloorX()));
				}
				break;
			case Axis::Y:
				$flipped->setPoint(new Vector3($selection->getPoint()->getX(), -$selection->getPos2()->getY() - $selection->getPoint()->getY(), $selection->getPoint()->getZ()));
				$selection->setPoint(Vector3::zero());
				$selection->useOnBlocks(function (int $x, int $y, int $z) use ($selection, $flipped): void {
					$block = $selection->getIterator()->getBlockAt($x, $y, $z);
					Selection::processBlock($block);
					$flipped->addBlock($x, $selection->getPos2()->getFloorY() - $y, $z, BlockRotationManipulator::flip(Axis::Y, $block));
				}, SelectionContext::full(), $selection);
				foreach ($selection->getTiles() as $tile) {
					$flipped->addTile(TileUtils::flipCompound(Axis::Y, $tile, $selection->getPos2()->getFloorY()));
				}
				break;
			case Axis::Z:
				$flipped->setPoint(new Vector3($selection->getPoint()->getX(), $selection->getPoint()->getY(), -$selection->getPos2()->getZ() - $selection->getPoint()->getZ()));
				$selection->setPoint(Vector3::zero());
				$selection->useOnBlocks(function (int $x, int $y, int $z) use ($selection, $flipped): void {
					$block = $selection->getIterator()->getBlockAt($x, $y, $z);
					Selection::processBlock($block);
					$flipped->addBlock($x, $y, $selection->getPos2()->getFloorZ() - $z, BlockRotationManipulator::flip(Axis::Z, $block));
				}, SelectionContext::full(), $selection);
				foreach ($selection->getTiles() as $tile) {
					$flipped->addTile(TileUtils::flipCompound(Axis::Z, $tile, $selection->getPos2()->getFloorZ()));
				}
				break;
			default:
				throw new UnexpectedValueException("Invalid axis " . $this->axis);
		}
		StorageModule::forceStore($this->saveId, $flipped);
		MessageSendData::from($this->getOwner(), Messages::replace("blocks-flipped", ["{time}" => (string) round(microtime(true) - $start, 2), "{changed}" => MixedUtils::humanReadable($flipped->getIterator()->getWrittenBlockCount())]));
	}

	public function getProgress(): float
	{
		return 0; //No splitting
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