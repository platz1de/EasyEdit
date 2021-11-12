<?php

namespace platz1de\EasyEdit\thread\output;

use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\LoaderManager;
use platz1de\EasyEdit\utils\ReferencedWorldHolder;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\FastChunkSerializer;
use pocketmine\world\World;

class ResultingChunkData extends OutputData
{
	use ReferencedWorldHolder;

	/**
	 * @var Chunk[]
	 */
	private array $chunkData;
	/**
	 * @var CompoundTag[]
	 */
	private array $tileData;

	/**
	 * @param string        $world
	 * @param Chunk[]       $chunks
	 * @param CompoundTag[] $tiles
	 */
	public static function from(string $world, array $chunks, array $tiles): void
	{
		$data = new self();
		$data->world = $world;
		$data->chunkData = $chunks;
		$data->tileData = $tiles;
		$data->send();
	}

	public function handle(): void
	{
		LoaderManager::setChunks($this->getWorld(), $this->getChunks(), $this->getTiles());
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->world);

		$chunks = new ExtendedBinaryStream();
		$count = 0;
		foreach ($this->chunkData as $hash => $chunk) {
			World::getXZ($hash, $x, $z);
			$chunks->putInt($x);
			$chunks->putInt($z);
			$chunks->putString(FastChunkSerializer::serializeTerrain($chunk));
			$count++;
		}
		$stream->putInt($count);
		$stream->put($chunks->getBuffer());

		$stream->putCompounds($this->tileData);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->world = $stream->getString();

		$count = $stream->getInt();
		$this->chunkData = [];
		for ($i = 0; $i < $count; $i++) {
			$this->chunkData[World::chunkHash($stream->getInt(), $stream->getInt())] = FastChunkSerializer::deserializeTerrain($stream->getString());
		}

		$this->tileData = $stream->getCompounds();
	}

	/**
	 * @return Chunk[]
	 */
	public function getChunks(): array
	{
		return $this->chunkData;
	}

	/**
	 * @return CompoundTag[]
	 */
	public function getTiles(): array
	{
		return $this->tileData;
	}
}