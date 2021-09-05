<?php

namespace platz1de\EasyEdit\selection;

use platz1de\EasyEdit\selection\cubic\CubicChunkLoader;
use platz1de\EasyEdit\selection\cubic\CubicIterator;
use platz1de\EasyEdit\task\ReferencedChunkManager;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\SafeSubChunkExplorer;
use pocketmine\block\tile\Tile;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\format\io\FastChunkSerializer;
use pocketmine\world\World;

abstract class BlockListSelection extends Selection
{
	use CubicChunkLoader;
	use CubicIterator;

	private ReferencedChunkManager $manager;
	private SafeSubChunkExplorer $iterator;
	/**
	 * @var CompoundTag[]
	 */
	private array $tiles = [];

	/**
	 * BlockListSelection constructor.
	 * @param string       $player
	 * @param string       $world
	 * @param Vector3|null $pos1
	 * @param Vector3|null $pos2
	 * @param bool         $piece
	 */
	public function __construct(string $player, string $world = "", ?Vector3 $pos1 = null, ?Vector3 $pos2 = null, bool $piece = false)
	{
		parent::__construct($player, $world, $pos1, $pos2, $piece);
		$this->manager = new ReferencedChunkManager($world);
		if ($pos1 instanceof Vector3 && $pos2 instanceof Vector3) {
			$this->getManager()->load($pos1, $pos2);
		}
		$this->iterator = new SafeSubChunkExplorer($this->manager);
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
		if ($id === 0) {
			$id = 0xD90; //structure_void
		}
		if ($overwrite || $this->iterator->getBlockAt($x, $y, $z) === 0) {
			$this->iterator->moveTo($x, $y, $z);
			$this->iterator->getCurrent()->setFullBlock($x & 0x0f, $y & 0x0f, $z & 0x0f, $id);
		}
	}

	/**
	 * @return SafeSubChunkExplorer
	 */
	public function getIterator(): SafeSubChunkExplorer
	{
		return $this->iterator;
	}

	/**
	 * @param CompoundTag $tile
	 */
	public function addTile(CompoundTag $tile): void
	{
		$this->tiles[World::blockHash($tile->getInt(Tile::TAG_X), $tile->getInt(Tile::TAG_Y), $tile->getInt(Tile::TAG_Z))] = $tile;
	}

	/**
	 * @return CompoundTag[]
	 */
	public function getTiles(): array
	{
		return $this->tiles;
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);

		$chunks = new ExtendedBinaryStream();
		$count = 0;
		foreach ($this->manager->getChunks() as $hash => $chunk) {
			World::getXZ($hash, $x, $z);
			$chunks->putString(FastChunkSerializer::serializeWithoutLight($chunk));
			$chunks->putInt($x);
			$chunks->putInt($z);
			$count++;
		}
		$stream->putInt($count);
		$stream->put($chunks->getBuffer());

		$stream->putCompounds($this->tiles);
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);

		$this->manager = new ReferencedChunkManager($this->getWorldName());

		$count = $stream->getInt();
		for ($i = 0; $i < $count; $i++) {
			$chunk = FastChunkSerializer::deserialize($stream->getString());
			$this->manager->setChunk($stream->getInt(), $stream->getInt(), $chunk);
		}

		$this->iterator = new SafeSubChunkExplorer($this->manager);

		$this->tiles = $stream->getCompounds();
	}

	public function free(): void
	{
		$this->manager->cleanChunks();
		$this->tiles = [];
	}
}