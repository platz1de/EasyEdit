<?php

namespace platz1de\EasyEdit\thread\modules;

use BadMethodCallException;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use platz1de\EasyEdit\selection\StaticBlockListSelection;
use platz1de\EasyEdit\utils\LoaderManager;
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
	 * @return int
	 */
	public static function finishCollecting(): int
	{
		if (self::$collected === null) {
			throw new BadMethodCallException("History should only collect existing pieces");
		}
		$id = self::nextStorageId();
		self::$storage[$id] = self::$collected;
		self::$collected = null;
		return $id;
	}

	/**
	 * @param BlockListSelection $piece
	 */
	public static function collect(BlockListSelection $piece): void
	{
		if (self::$collected === null) {
			self::$collected = $piece;
		} else {
			foreach ($piece->getManager()->getChunks() as $hash => $chunk) {
				World::getXZ($hash, $x, $z);
				//TODO: only create Chunks which are really needed
				if (LoaderManager::isChunkUsed($chunk)) {
					self::$collected->getManager()->setChunk($x, $z, $chunk);
				}
			}
			foreach ($piece->getTiles() as $tile) {
				self::$collected->addTile($tile);
			}
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
	 * @param int $id
	 * @return BlockListSelection
	 */
	public static function getStored(int $id): BlockListSelection
	{
		$toClone = self::$storage[$id];
		$class = $toClone::class;
		$selection = new $class($toClone->getPlayer());
		$selection->setPos1($toClone->getPos1());
		$selection->setPos2($toClone->getPos2());
		if ($selection instanceof DynamicBlockListSelection && $toClone instanceof DynamicBlockListSelection) {
			$selection->setPoint($toClone->getPoint());
		} elseif ($selection instanceof StaticBlockListSelection && $toClone instanceof StaticBlockListSelection) {
			$selection->setWorld($toClone->getWorldName());
		} else {
			throw new UnexpectedValueException("Invalid selection of type " . $class . " saved at id " . $id);
		}
		foreach ($toClone->getManager()->getChunks() as $hash => $chunk) {
			World::getXZ($hash, $x, $z);
			$selection->getManager()->setChunk($x, $z, $chunk);
		}
		foreach ($toClone->getTiles() as $tile) {
			$selection->addTile($tile);
		}
		return $selection;
	}

	/**
	 * @param int $id
	 */
	public static function cleanStored(int $id): void
	{
		unset(self::$storage[$id]);
	}
}