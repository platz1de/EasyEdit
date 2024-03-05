<?php

namespace platz1de\EasyEdit\environment;

use platz1de\EasyEdit\task\editing\ChunkedTask;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\GroupedChunkHandler;
use platz1de\EasyEdit\thread\chunk\ChunkHandler;
use platz1de\EasyEdit\thread\chunk\ChunkRequest;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\utils\LoaderManager;
use platz1de\EasyEdit\world\blockupdate\InjectingData;
use platz1de\EasyEdit\world\blockupdate\InjectingSubChunkController;
use platz1de\EasyEdit\world\ChunkController;
use platz1de\EasyEdit\world\ChunkInformation;
use platz1de\EasyEdit\world\HeightMapCache;
use platz1de\EasyEdit\world\ReferencedChunkManager;
use pocketmine\block\tile\Tile;
use pocketmine\Server;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\world\format\Chunk;
use pocketmine\world\World;
use UnexpectedValueException;

/**
 * The main thread processes the tasks synchronously
 * Only very short tasks should be processed in the main thread
 */
class MainThreadHandler extends ThreadEnvironmentHandler
{
	/**
	 * @var ChunkRequest[]
	 */
	private array $chunkRequestQueue = [];
	private bool $isChunkPaused = false;

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

	public function initChunkHandler(ChunkHandler $handler): void {
		$this->chunkRequestQueue = [];
		$this->isChunkPaused = false;
	}

	public function processChunkRequest(ChunkRequest $chunk, ChunkHandler $handler): void
	{
		if ($this->isChunkPaused) {
			$this->chunkRequestQueue[] = $chunk;
		} else {
			$this->executeChunkRequest($chunk, $handler);
		}
	}

	public function executeChunkRequest(ChunkRequest $chunk, ChunkHandler $handler): void
	{
		World::getXZ($chunk->getChunk(), $x, $z);
		if ($chunk->getWorld()->isChunkLoaded($x, $z)) {
			World::getXZ($chunk->getChunk(), $x, $z);
			$c = $chunk->getWorld()->getChunk($x, $z);
			if (!$c instanceof Chunk) {
				throw new AssumptionFailedError();
			}
			$tiles = array_map(static function (Tile $tile) {
				return $tile->saveNBT();
			}, $c->getTiles());
			$c = clone $c;
			foreach ($c->getTiles() as $tile) {
				$c->removeTile($tile);
			}
			$handler->handleInput($chunk->getChunk(), new ChunkInformation($c, $tiles), $chunk->getPayload());
		} else {
			EditThread::getInstance()->getLogger()->error("Chunk " . $chunk->getChunk() . " was unloaded while processing");
		}
	}

	public function finalizeChunkStep(): void { }

	public function postProgress(float $progress): void { }

	public function getChunkController(ReferencedChunkManager $manager): ChunkController
	{
		return new InjectingSubChunkController($manager);
	}

	/**
	 * @param ChunkedTask         $task
	 * @param GroupedChunkHandler $chunkHandler
	 * @param EditTaskHandler     $editHandler
	 * @param int[]               $chunks
	 */
	public function executeChunkedTask(ChunkedTask $task, GroupedChunkHandler $chunkHandler, EditTaskHandler $editHandler, array $chunks): void
	{
		$constructors = iterator_to_array($task->prepareConstructors($editHandler), false);
		$this->isChunkPaused = true;
		$chunkHandler->requestAll($chunks, $constructors);
		$this->isChunkPaused = false;
		//Process all at once so relations are correct
		foreach ($this->chunkRequestQueue as $chunk) {
			$this->executeChunkRequest($chunk, $chunkHandler);
		}
		while (($key = $chunkHandler->getNextChunk()) !== null) {
			foreach ($chunkHandler->getData() as $k => $information) {
				$editHandler->setChunk($k, $information);
			}

			HeightMapCache::prepare();

			foreach ($constructors as $constructor) {
				$constructor->moveTo($key);
			}

			$editHandler->finish();
		}
	}
}