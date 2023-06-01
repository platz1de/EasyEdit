<?php

namespace platz1de\EasyEdit\thread\chunk;

use platz1de\EasyEdit\session\SessionIdentifier;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\thread\input\ChunkInputData;
use platz1de\EasyEdit\thread\input\task\CancelTaskData;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\LoaderManager;
use platz1de\EasyEdit\world\ChunkInformation;
use pocketmine\block\tile\Tile;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\format\io\ChunkData;
use pocketmine\world\World;
use Throwable;

class ChunkRequestExecutor
{
	use SingletonTrait;

	public function addRequest(ChunkRequest $request): void
	{
		World::getXZ($request->getChunk(), $x, $z);

		try {
			$request->getWorld();
		} catch (Throwable $e) {
			EditThread::getInstance()->getLogger()->warning($e->getMessage());
			CancelTaskData::from(SessionIdentifier::internal("Chunkloading Failed"));
			return;
		}

		if ($request->getWorld()->isChunkLoaded($x, $z)) {
			//We can handle this request right away
			ChunkInputData::from($this->addChunk($request->getWorld(), $x, $z, new ExtendedBinaryStream()), $request->getPayload());
			return;
		}

		$request->getWorld()->requestChunkPopulation($x, $z, null)->onCompletion(
			function () use ($request, $x, $z): void {
				ChunkInputData::from($this->addChunk($request->getWorld(), $x, $z, new ExtendedBinaryStream()), $request->getPayload());
			},
			function () use ($x, $z): void {
				CancelTaskData::from(SessionIdentifier::internal("Chunkloading Failed"));
				EditThread::getInstance()->getLogger()->warning("Failed to load chunk $x $z");
			}
		);
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