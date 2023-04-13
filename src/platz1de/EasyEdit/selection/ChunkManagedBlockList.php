<?php

namespace platz1de\EasyEdit\selection;

use platz1de\EasyEdit\math\BlockVector;
use platz1de\EasyEdit\task\CancelException;
use platz1de\EasyEdit\thread\block\BlockStateTranslationManager;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\world\ChunkController;
use platz1de\EasyEdit\world\ChunkInformation;
use platz1de\EasyEdit\world\ReferencedChunkManager;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\world\World;

abstract class ChunkManagedBlockList extends BlockListSelection
{
	private ReferencedChunkManager $manager;
	private ChunkController $iterator;

	/**
	 * BlockListSelection constructor.
	 * @param string      $world
	 * @param BlockVector $pos1
	 * @param BlockVector $pos2
	 */
	public function __construct(string $world, BlockVector $pos1, BlockVector $pos2)
	{
		parent::__construct($world, $pos1, $pos2);
		$this->manager = new ReferencedChunkManager($world);
		$this->getManager()->loadBetween($pos1, $pos2);
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
		if ($overwrite || $this->iterator->getBlock($x, $y, $z) === 0) {
			$this->iterator->setBlock($x, $y, $z, $id);
		}
	}

	/**
	 * @return BlockStateData[]
	 * @throws CancelException
	 */
	public function requestBlockStates(): array
	{
		return BlockStateTranslationManager::requestBlockState($this->iterator->collectPalette($this->pos1, $this->pos2));
	}

	/**
	 * @return int[]
	 */
	public function getNeededChunks(): array
	{
		return $this->getNonEmptyChunks($this->pos1, $this->pos2);
	}

	/**
	 * @param BlockVector $start
	 * @param BlockVector $end
	 * @return int[]
	 */
	protected function getNonEmptyChunks(BlockVector $start, BlockVector $end): array
	{
		$chunks = [];
		for ($x = $start->x >> 4; $x <= $end->x >> 4; $x++) {
			for ($z = $start->z >> 4; $z <= $end->z >> 4; $z++) {
				$chunk = World::chunkHash($x, $z);
				if (!$this->manager->getChunk($chunk)->isEmpty()) {
					$chunks[] = $chunk;
				}
			}
		}
		return $chunks;
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

	public function containsData(): bool
	{
		foreach ($this->getManager()->getChunks() as $chunk) {
			if ($chunk->wasUsed()) {
				return true;
			}
		}
		return parent::containsData();
	}
}