<?php

namespace platz1de\EasyEdit\thread\output;

use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\LoaderManager;
use platz1de\EasyEdit\world\ChunkInformation;
use platz1de\EasyEdit\world\ReferencedWorldHolder;
use pocketmine\world\World;

class ResultingChunkData extends OutputData
{
	use ReferencedWorldHolder;

	/**
	 * @var ChunkInformation[]
	 */
	private array $chunkData;
	/**
	 * @var string[]
	 */
	private array $injections = [];

	/**
	 * @param string             $world
	 * @param ChunkInformation[] $chunks
	 */
	public static function from(string $world, array $chunks): void
	{
		if ($chunks === []) {
			return;
		}
		$data = new self();
		$data->world = $world;
		$data->chunkData = $chunks;
		$data->send();
	}

	/**
	 * @param string             $world
	 * @param ChunkInformation[] $chunks
	 * @param string[]           $injections UpdateSubChunkBlocksPacket data
	 */
	public static function withInjection(string $world, array $chunks, array $injections): void
	{
		if ($chunks === [] && $injections === []) {
			return;
		}
		$data = new self();
		$data->world = $world;
		$data->chunkData = $chunks;
		$data->injections = $injections;
		$data->send();
	}

	public function handle(): void
	{
		LoaderManager::setChunks($this->getWorld(), $this->getChunks(), $this->getInjections());
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
			$chunk->putData($chunks);
			$count++;
		}
		$stream->putInt($count);
		$stream->put($chunks->getBuffer());

		$stream->putInt(count($this->injections));
		foreach ($this->injections as $hash => $injection) {
			$stream->putLong($hash);
			$stream->putString($injection);
		}
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->world = $stream->getString();

		$count = $stream->getInt();
		$this->chunkData = [];
		for ($i = 0; $i < $count; $i++) {
			$this->chunkData[World::chunkHash($stream->getInt(), $stream->getInt())] = ChunkInformation::readFrom($stream);
		}

		$count = $stream->getInt();
		for ($i = 0; $i < $count; $i++) {
			$this->injections[$stream->getLong()] = $stream->getString();
		}
	}

	/**
	 * @return ChunkInformation[]
	 */
	public function getChunks(): array
	{
		return $this->chunkData;
	}

	/**
	 * @return string[]
	 */
	public function getInjections(): array
	{
		return $this->injections;
	}
}