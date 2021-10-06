<?php

namespace platz1de\EasyEdit\worker\modules;

use BadMethodCallException;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\StaticBlockListSelection;
use platz1de\EasyEdit\utils\LoaderManager;
use pocketmine\world\World;

class StorageModule
{
	/**
	 * @var BlockListSelection[]
	 */
	private static array $storage = [];
	private static int $storageSlot = 0;
	private static ?StaticBlockListSelection $collected = null;

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
	 * @param StaticBlockListSelection $piece
	 */
	public static function collect(StaticBlockListSelection $piece): void
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
		return self::$storage[$id];
	}

	/**
	 * @param int $id
	 */
	public static function cleanStored(int $id): void
	{
		unset(self::$storage[$id]);
	}
}