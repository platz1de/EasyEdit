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
	 * @var string[]
	 */
	private array $injections = [];

	/**
	 * @param string        $world
	 * @param Chunk[]       $chunks
	 * @param CompoundTag[] $tiles
	 */
	public static function from(string $world, array $chunks, array $tiles): void
	{
		if ($chunks === []) {
			return;
		}
		$data = new self();
		$data->world = $world;
		$data->chunkData = $chunks;
		$data->tileData = $tiles;
		$data->send();
	}

	/**
	 * @param string        $world
	 * @param Chunk[]       $chunks
	 * @param CompoundTag[] $tiles
	 * @param string[]      $injections UpdateSubChunkBlocksPacket data
	 */
	public static function withInjection(string $world, array $chunks, array $tiles, array $injections): void
	{
		if ($chunks === [] && $injections === []) {
			return;
		}
		$data = new self();
		$data->world = $world;
		$data->chunkData = $chunks;
		$data->tileData = $tiles;
		$data->injections = $injections;
		$data->send();
	}

	public function handle(): void
	{
		LoaderManager::setChunks($this->getWorld(), $this->getChunks(), $this->getTiles(), $this->getInjections());
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

		$stream->putInt(count($this->injections));
		foreach ($this->injections as $hash => $injection) {
			$stream->putInt($hash);
			$stream->putString($injection);
		}
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

		$count = $stream->getInt();
		for ($i = 0; $i < $count; $i++) {
			$this->injections[$stream->getInt()] = $stream->getString();
		}
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

	/**
	 * @return string[]
	 */
	public function getInjections(): array
	{
		return $this->injections;
	}
}