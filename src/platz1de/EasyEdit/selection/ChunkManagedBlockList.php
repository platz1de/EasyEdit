<?php

namespace platz1de\EasyEdit\selection;

use BadMethodCallException;
use platz1de\EasyEdit\selection\cubic\CubicChunkLoader;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\LoaderManager;
use platz1de\EasyEdit\world\ChunkController;
use platz1de\EasyEdit\world\ChunkInformation;
use platz1de\EasyEdit\world\ReferencedChunkManager;
use pocketmine\math\Vector3;
use pocketmine\world\World;

abstract class ChunkManagedBlockList extends BlockListSelection
{
	use CubicChunkLoader;

	private ReferencedChunkManager $manager;
	private ChunkController $iterator;

	/**
	 * BlockListSelection constructor.
	 * @param string  $player
	 * @param string  $world
	 * @param Vector3 $pos1
	 * @param Vector3 $pos2
	 * @param bool    $piece
	 */
	public function __construct(string $player, string $world, Vector3 $pos1, Vector3 $pos2, bool $piece = false)
	{
		parent::__construct($player, $world, $pos1, $pos2, $piece);
		$this->manager = new ReferencedChunkManager($world);
		$this->getManager()->loadBetween($this->pos1, $this->pos2);
		$this->iterator = new ChunkController($this->manager);
	}

	/**
	 * @return ReferencedChunkManager
	 */
	public function getManager(): ReferencedChunkManager
	{
		return $this->manager;
	}

	/**
	 * @param int  $x
	 * @param int  $y
	 * @param int  $z
	 * @param int  $id
	 * @param bool $overwrite
	 */
	public function addBlock(int $x, int $y, int $z, int $id, bool $overwrite = true): void
	{
		if ($id === 0xD90) {
			return;
		}
		if ($id === 0) {
			$id = 0xD90; //structure_void
		}
		if ($overwrite || $this->iterator->getBlock($x, $y, $z) === 0) {
			$this->iterator->setBlock($x, $y, $z, $id);
		}
	}

	/**
	 * @return ChunkController
	 */
	public function getIterator(): ChunkController
	{
		return $this->iterator;
	}

	public function getBlockCount(): int
	{
		return $this->iterator->getWrittenBlockCount();
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);

		$chunks = new ExtendedBinaryStream();
		$count = 0;
		foreach ($this->manager->getChunks() as $hash => $chunk) {
			World::getXZ($hash, $x, $z);
			$chunks->putInt($x);
			$chunks->putInt($z);
			$chunk->putData($stream);
			$count++;
		}
		$stream->putInt($count);
		$stream->put($chunks->getBuffer());
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);

		$this->manager = new ReferencedChunkManager($this->getWorldName());

		$count = $stream->getInt();
		for ($i = 0; $i < $count; $i++) {
			$this->manager->setChunk(World::chunkHash($stream->getInt(), $stream->getInt()), ChunkInformation::readFrom($stream));
		}

		$this->iterator = new ChunkController($this->manager);
	}

	public function free(): void
	{
		parent::free();
		$this->manager->cleanChunks();
	}

	/**
	 * @param BlockListSelection $selection
	 */
	public function merge(BlockListSelection $selection): void
	{
		if (!$selection instanceof self) {
			throw new BadMethodCallException("Can't merge block lists of different types");
		}

		parent::merge($selection);

		foreach ($selection->getManager()->getChunks() as $hash => $chunk) {
			//TODO: only create Chunks which are really needed
			if (LoaderManager::isChunkUsed($chunk)) {
				$this->getManager()->setChunk($hash, $chunk);
			}
		}
	}

	public function checkCachedData(): void
	{
		$collected = StorageModule::getCurrentCollected();
		if (!$collected instanceof self) {
			return;
		}
		foreach ($this->getManager()->getChunks() as $hash => $chunk) {
			if (isset($collected->getManager()->getChunks()[$hash])) {
				$this->getManager()->setChunk($hash, $collected->getManager()->getChunks()[$hash]);
			}
		}
	}
}