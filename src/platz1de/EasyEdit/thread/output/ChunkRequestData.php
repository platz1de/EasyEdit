<?php

namespace platz1de\EasyEdit\thread\output;

use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\thread\input\ChunkInputData;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\LoaderManager;
use platz1de\EasyEdit\utils\ReferencedWorldHolder;
use pocketmine\world\format\io\ChunkData;
use pocketmine\world\format\io\FastChunkSerializer;
use pocketmine\world\World;
use UnexpectedValueException;

class ChunkRequestData extends OutputData
{
	use ReferencedWorldHolder;

	/**
	 * @var int[]
	 */
	private array $chunks = [];

	/**
	 * @param int[]  $chunks
	 * @param string $world
	 * @return ChunkRequestData
	 */
	public static function from(array $chunks, string $world): ChunkRequestData
	{
		$data = new self();
		$data->chunks = $chunks;
		$data->world = $world;
		return $data;
	}

	public function handle(): void
	{
		$this->prepareNextChunk($this->chunks, $this->getWorld(), new ExtendedBinaryStream(), new ExtendedBinaryStream());
	}

	/**
	 * @param int[]                $chunks
	 * @param World                $world
	 * @param ExtendedBinaryStream $chunkData
	 * @param ExtendedBinaryStream $tileData
	 */
	private function prepareNextChunk(array $chunks, World $world, ExtendedBinaryStream $chunkData, ExtendedBinaryStream $tileData): void
	{
		World::getXZ((int) array_pop($chunks), $x, $z);

		$world->orderChunkPopulation($x, $z, null)->onCompletion(
			function () use ($tileData, $chunkData, $z, $x, $world, $chunks): void {
				$chunkData->putInt($x);
				$chunkData->putInt($z);
				$chunk = LoaderManager::getChunk($world, $x, $z);
				if ($chunk instanceof ChunkData) {
					foreach ($chunk->getTileNBT() as $tile) {
						$tileData->putCompound($tile);
					}

					$chunk = $chunk->getChunk();
				} else {
					foreach ($chunk->getTiles() as $tile) {
						$tileData->putCompound($tile->saveNBT());
					}
				}
				$chunkData->putString(FastChunkSerializer::serializeWithoutLight($chunk));

				if ($chunks === []) {
					EasyEdit::getWorker()->sendToThread(ChunkInputData::from($chunkData->getBuffer(), $tileData->getBuffer()));
				} else {
					$this->prepareNextChunk($chunks, $world, $chunkData, $tileData);
				}
			},
			function () use ($z, $x): void {
				throw new UnexpectedValueException("Failed to prepare Chunk " . $x . " " . $z);
			}
		);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->world);

		$stream->putInt(count($this->chunks));
		foreach ($this->chunks as $chunk) {
			$stream->putInt($chunk);
		}
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->world = $stream->getString();

		$count = $stream->getInt();
		for ($i = 0; $i < $count; $i++) {
			$this->chunks[] = $stream->getInt();
		}
	}
}