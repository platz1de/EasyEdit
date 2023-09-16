<?php

namespace platz1de\EasyEdit\environment;

use platz1de\EasyEdit\thread\chunk\ChunkHandler;
use platz1de\EasyEdit\thread\chunk\ChunkRequest;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\utils\LoaderManager;
use platz1de\EasyEdit\world\blockupdate\InjectingData;
use platz1de\EasyEdit\world\blockupdate\InjectingSubChunkController;
use platz1de\EasyEdit\world\ChunkController;
use platz1de\EasyEdit\world\ChunkInformation;
use pocketmine\block\tile\Tile;
use pocketmine\Server;
use pocketmine\world\format\io\ChunkData;
use pocketmine\world\World;
use UnexpectedValueException;

/**
 * The main thread processes the tasks synchronously
 * Only very short tasks should be processed in the main thread
 */
class MainThreadHandler extends ThreadEnvironmentHandler
{
	public function submitResultingChunks(ChunkController $controller): void
	{
		if (!$controller instanceof InjectingSubChunkController) {
			$injections = [];
		} else {
			$injections = array_map(static function (InjectingData $injection) {
				return $injection->toProtocol();
			}, $controller->getInjections());
		}
		LoaderManager::setChunks($controller->getManager()->getWorld(), $controller->getManager()->getModifiedChunks(), $injections);
	}

	/**
	 * @param string           $world
	 * @param int              $index
	 * @param ChunkInformation $chunk
	 * @param string[]         $injections
	 */
	public function submitSingleChunk(string $world, int $index, ChunkInformation $chunk, array $injections): void
	{
		$w = Server::getInstance()->getWorldManager()->getWorldByName($world);
		if ($w === null) {
			throw new UnexpectedValueException("World " . $world . " was deleted, unloaded or renamed");
		}
		LoaderManager::setChunks($w, [$index => $chunk], $injections);
	}

	public function initChunkHandler(ChunkHandler $handler): void { }

	public function processChunkRequest(ChunkRequest $chunk, ChunkHandler $handler): void
	{
		World::getXZ($chunk->getChunk(), $x, $z);
		if ($chunk->getWorld()->isChunkLoaded($x, $z)) {
			$this->chunkRequestCallback($chunk, $handler);
		} else {
			$chunk->getWorld()->requestChunkPopulation($x, $z, null)->onCompletion(
				function () use ($chunk, $handler): void {
					$this->chunkRequestCallback($chunk, $handler);
				},
				function () use ($x, $z): void {
					EditThread::getInstance()->getLogger()->warning("Failed to load chunk $x $z");
				}
			);
		}
	}

	private function chunkRequestCallback(ChunkRequest $chunk, ChunkHandler $handler): void
	{
		World::getXZ($chunk->getChunk(), $x, $z);
		$c = LoaderManager::getChunk($chunk->getWorld(), $x, $z);
		if ($c instanceof ChunkData) {
			$tiles = $c->getTileNBT();
			$c = $c->getChunk();
		} else {
			$tiles = array_map(static function (Tile $tile) {
				return $tile->saveNBT();
			}, $c->getTiles());
		}
		$handler->handleInput($chunk->getChunk(), new ChunkInformation($c, $tiles), $chunk->getPayload());
	}

	public function finalizeChunkStep(): void { }

	public function postProgress(float $progress): void { }
}