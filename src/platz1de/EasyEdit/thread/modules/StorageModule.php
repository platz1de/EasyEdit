<?php

namespace platz1de\EasyEdit\thread\modules;

use BadMethodCallException;
use platz1de\EasyEdit\selection\BinaryBlockListStream;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\ChunkManagedBlockList;
use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\selection\StaticBlockListSelection;
use pocketmine\world\World;
use UnexpectedValueException;

class StorageModule
{
	/**
	 * @var BlockListSelection[]
	 */
	private static array $storage = [];
	private static int $storageSlot = 0;
	private static ?BlockListSelection $collected = null;

	/**
	 * @return StoredSelectionIdentifier
	 */
	public static function finishCollecting(): StoredSelectionIdentifier
	{
		if (self::$collected === null) {
			throw new BadMethodCallException("History should only collect existing pieces");
		}
		$id = self::nextStorageId();
		self::$storage[$id] = self::$collected;
		$identifier = new StoredSelectionIdentifier($id, self::$collected::class);
		self::$collected = null;
		return $identifier;
	}

	/**
	 * @param BlockListSelection $piece
	 */
	public static function collect(BlockListSelection $piece): void
	{
		if (self::$collected === null) {
			self::$collected = $piece;
		} else {
			self::$collected->merge($piece);
		}
	}

	/**
	 * @return int
	 */
	public static function nextStorageId(): int
	{
		return self::$storageSlot++;
	}

	/**
	 * @param StoredSelectionIdentifier $id
	 * @return BlockListSelection
	 */
	public static function getStored(StoredSelectionIdentifier $id): BlockListSelection
	{
		$toClone = self::$storage[$id->getMagicId()];
		$class = $toClone::class;
		$selection = new $class($toClone->getPlayer());
		if ($selection instanceof ChunkManagedBlockList && $toClone instanceof ChunkManagedBlockList) {
			$selection->setPos1($toClone->getPos1());
			$selection->setPos2($toClone->getPos2());

			if ($selection instanceof DynamicBlockListSelection && $toClone instanceof DynamicBlockListSelection) {
				$selection->setPoint($toClone->getPoint());
			} elseif ($selection instanceof StaticBlockListSelection && $toClone instanceof StaticBlockListSelection) {
				$selection->setWorld($toClone->getWorldName());
			}

			foreach ($toClone->getManager()->getChunks() as $hash => $chunk) {
				World::getXZ($hash, $x, $z);
				$selection->getManager()->setChunk($x, $z, $chunk);
			}
		} elseif ($selection instanceof BinaryBlockListStream && $toClone instanceof BinaryBlockListStream) {
			$selection->setData($toClone->getData());
			$selection->setWorld($toClone->getWorldName());
		} else {
			throw new UnexpectedValueException("Invalid selection of type " . $class . " saved at id " . $id->getMagicId() . ", expected " . $id->getType());
		}
		foreach ($toClone->getTiles() as $tile) {
			$selection->addTile($tile);
		}
		return $selection;
	}

	/**
	 * @param StoredSelectionIdentifier $id
	 * @return StaticBlockListSelection|BinaryBlockListStream
	 */
	public static function mustGetStatic(StoredSelectionIdentifier $id): StaticBlockListSelection|BinaryBlockListStream
	{
		$selection = self::getStored($id);
		if ($selection instanceof StaticBlockListSelection || $selection instanceof BinaryBlockListStream) {
			return $selection;
		}
		throw new UnexpectedValueException("Invalid selection of type " . $selection::class . " saved at id " . $id->getMagicId() . ", expected " . StaticBlockListSelection::class . " or " . BinaryBlockListStream::class);
	}

	/**
	 * @param StoredSelectionIdentifier $id
	 * @return DynamicBlockListSelection
	 */
	public static function mustGetDynamic(StoredSelectionIdentifier $id): DynamicBlockListSelection
	{
		$selection = self::getStored($id);
		if ($selection instanceof DynamicBlockListSelection) {
			return $selection;
		}
		throw new UnexpectedValueException("Invalid selection of type " . $selection::class . " saved at id " . $id->getMagicId() . ", expected " . DynamicBlockListSelection::class);
	}

	/**
	 * @param StoredSelectionIdentifier $id
	 * @param BlockListSelection        $selection
	 */
	public static function forceStore(StoredSelectionIdentifier $id, BlockListSelection $selection): void
	{
		self::$storage[$id->getMagicId()] = $selection;
	}

	/**
	 * @param StoredSelectionIdentifier $id
	 */
	public static function cleanStored(StoredSelectionIdentifier $id): void
	{
		unset(self::$storage[$id->getMagicId()]);
	}

	/**
	 * @return int
	 */
	public static function getSize(): int
	{
		return count(self::$storage);
	}
}