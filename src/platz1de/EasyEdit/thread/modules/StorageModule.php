<?php

namespace platz1de\EasyEdit\thread\modules;

use platz1de\EasyEdit\selection\BinaryBlockListStream;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use platz1de\EasyEdit\selection\identifier\SelectionIdentifier;
use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\selection\NonSavingBlockListSelection;
use platz1de\EasyEdit\selection\StaticBlockListSelection;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\thread\input\task\InsertStorageTask;
use pmmp\thread\Thread;
use UnexpectedValueException;

/**
 * Important Notice: EVERY case of unused selection identifiers (e.g. as output of a task) must be unregistered!
 * This will otherwise lead to memory leaks by keeping the selection in memory forever.
 */
class StorageModule
{
	/**
	 * @var BlockListSelection[]
	 */
	private static array $storage = [];
	private static int $storageSlot = 0;

	/**
	 * @param BlockListSelection $selection
	 * @return StoredSelectionIdentifier
	 */
	public static function store(BlockListSelection $selection): StoredSelectionIdentifier
	{
		if ($selection instanceof NonSavingBlockListSelection) {
			return StoredSelectionIdentifier::invalid();
		}
		if (Thread::getCurrentThread() instanceof EditThread) {
			$id = ++self::$storageSlot;
			self::$storage[$id] = $selection;
			$identifier = new StoredSelectionIdentifier($id);
			EditThread::getInstance()->getStats()->updateStorage(count(self::$storage));
			return $identifier;
		}
		$identifier = new StoredSelectionIdentifier(--self::$storageSlot);
		InsertStorageTask::from($identifier, $selection);
		return $identifier;
	}

	/**
	 * @param StoredSelectionIdentifier $id
	 * @return BlockListSelection
	 */
	public static function getStored(StoredSelectionIdentifier $id): BlockListSelection
	{
		if ($id->isOneTime()) {
			$return = self::$storage[$id->getMagicId()];
			self::cleanStored($id);
			return $return;
		}
		return self::$storage[$id->getMagicId()]->createSafeClone();
	}

	/**
	 * @param SelectionIdentifier $id
	 * @return StaticBlockListSelection|BinaryBlockListStream
	 */
	public static function mustGetStatic(SelectionIdentifier $id): StaticBlockListSelection|BinaryBlockListStream
	{
		if (!$id instanceof StoredSelectionIdentifier) {
			if ($id instanceof StaticBlockListSelection || $id instanceof BinaryBlockListStream) {
				return $id;
			}
			throw new UnexpectedValueException("Invalid selection of type " . $id::class . ", expected " . StaticBlockListSelection::class . " or " . BinaryBlockListStream::class);
		}
		$selection = self::getStored($id);
		if ($selection instanceof StaticBlockListSelection || $selection instanceof BinaryBlockListStream) {
			return $selection;
		}
		throw new UnexpectedValueException("Invalid selection of type " . $selection::class . " saved at id " . $id->getMagicId() . ", expected " . StaticBlockListSelection::class . " or " . BinaryBlockListStream::class);
	}

	/**
	 * @param SelectionIdentifier $id
	 * @return DynamicBlockListSelection
	 */
	public static function mustGetDynamic(SelectionIdentifier $id): DynamicBlockListSelection
	{
		if (!$id instanceof StoredSelectionIdentifier) {
			if ($id instanceof DynamicBlockListSelection) {
				return $id;
			}
			throw new UnexpectedValueException("Invalid selection of type " . $id::class . ", expected " . DynamicBlockListSelection::class);
		}
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
		EditThread::getInstance()->getStats()->updateStorage(count(self::$storage));
	}

	/**
	 * @param StoredSelectionIdentifier $id
	 */
	public static function cleanStored(StoredSelectionIdentifier $id): void
	{
		unset(self::$storage[$id->getMagicId()]);
		EditThread::getInstance()->getStats()->updateStorage(count(self::$storage));
	}
}