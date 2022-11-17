<?php

namespace platz1de\EasyEdit\thread\chunk;

use platz1de\EasyEdit\thread\input\ChunkInputData;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\LoaderManager;
use platz1de\EasyEdit\world\ChunkInformation;
use pocketmine\block\tile\Tile;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\format\io\ChunkData;
use pocketmine\world\World;
use RuntimeException;

class ChunkRequestExecutor
{
	use SingletonTrait;

	private const MAX_CHUNKS_PER_TICK = 4;
	/**
	 * @var ChunkRequest[]
	 */
	private array $requestQueue = [];
	private int $counter = 0;

	public function addRequest(ChunkRequest $request): void
	{
		if ($request->getType() === ChunkRequest::TYPE_PRIORITY) {
			World::getXZ($request->getChunk(), $x, $z);
			ChunkInputData::from($this->addChunk($request->getWorld(), $x, $z, new ExtendedBinaryStream()), $request->getPayload());
			return;
		}
		$this->handleRequest($request);
	}

	private function handleRequest(ChunkRequest $request): void
	{
		World::getXZ($request->getChunk(), $x, $z);

		if ($request->getWorld()->isChunkLoaded($x, $z)) {
			//We can handle this request right away
			ChunkInputData::from($this->addChunk($request->getWorld(), $x, $z, new ExtendedBinaryStream()), $request->getPayload());
			return;
		}

		if ($this->counter++ >= self::MAX_CHUNKS_PER_TICK) {
			$this->requestQueue[] = $request;
			return;
		}
		$request->getWorld()->requestChunkPopulation($x, $z, null)->onCompletion(
			function () use ($request, $x, $z): void {
				ChunkInputData::from($this->addChunk($request->getWorld(), $x, $z, new ExtendedBinaryStream()), $request->getPayload());
			},
			function () use ($x, $z): void {
				throw new RuntimeException("Failed to load chunk $x $z");
			}
		);
	}

	public function doTick(): void
	{
		$this->counter = 0;
		while (count($this->requestQueue) > 0 && $this->counter < self::MAX_CHUNKS_PER_TICK) {
			$this->handleRequest(array_shift($this->requestQueue));
		}
	}

	/**
	 * @param World                $world
	 * @param int                  $x
	 * @param int                  $z
	 * @param ExtendedBinaryStream $stream
	 * @return string
	 */
	private function addChunk(World $world, int $x, int $z, ExtendedBinaryStream $stream): string
	{
		$stream->putInt($x);
		$stream->putInt($z);
		$chunk = LoaderManager::getChunk($world, $x, $z);
		if ($chunk instanceof ChunkData) {
			//TODO: can this even happen?
			$tiles = $chunk->getTileNBT();
			$chunk = $chunk->getChunk();
		} else {
			$tiles = array_map(static function (Tile $tile) {
				return $tile->saveNBT();
			}, $chunk->getTiles());
		}
		(new ChunkInformation($chunk, $tiles))->putData($stream);
		return $stream->getBuffer();
	}
}