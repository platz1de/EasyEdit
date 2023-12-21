<?php

namespace platz1de\EasyEdit\environment;

use platz1de\EasyEdit\task\CancelException;
use platz1de\EasyEdit\task\editing\ChunkedTask;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\GroupedChunkHandler;
use platz1de\EasyEdit\thread\chunk\ChunkHandler;
use platz1de\EasyEdit\thread\chunk\ChunkRequest;
use platz1de\EasyEdit\thread\chunk\ChunkRequestManager;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\thread\output\ResultingChunkData;
use platz1de\EasyEdit\world\blockupdate\InjectingData;
use platz1de\EasyEdit\world\blockupdate\InjectingSubChunkController;
use platz1de\EasyEdit\world\ChunkController;
use platz1de\EasyEdit\world\ChunkInformation;
use platz1de\EasyEdit\world\HeightMapCache;
use platz1de\EasyEdit\world\ReferencedChunkManager;

/**
 * The edit thread processes the tasks asynchronously
 */
class EditThreadHandler extends ThreadEnvironmentHandler
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
		$data = new ResultingChunkData($controller->getManager()->getWorldName(), $controller->getManager()->getModifiedChunks(), $injections);
		if ($data->checkSend()) {
			EditThread::getInstance()->sendOutput($data);
		}
	}

	/**
	 * @param string           $world
	 * @param int              $index
	 * @param ChunkInformation $chunk
	 * @param string[]         $injections
	 */
	public function submitSingleChunk(string $world, int $index, ChunkInformation $chunk, array $injections): void
	{
		EditThread::getInstance()->sendOutput(new ResultingChunkData($world, [$index => $chunk], $injections));
	}

	public function initChunkHandler(ChunkHandler $handler): void
	{
		ChunkRequestManager::setHandler($handler);
	}

	public function processChunkRequest(ChunkRequest $chunk, ChunkHandler $handler): void
	{
		ChunkRequestManager::addRequest($chunk);
	}

	public function finalizeChunkStep(): void
	{
		ChunkRequestManager::markAsDone();
	}

	public function postProgress(float $progress): void
	{
		EditThread::getInstance()->getStats()->updateProgress($progress);
	}

	public function getChunkController(ReferencedChunkManager $manager): ChunkController
	{
		return new ChunkController($manager);
	}

	/**
	 * @param ChunkedTask         $task
	 * @param GroupedChunkHandler $chunkHandler
	 * @param EditTaskHandler     $editHandler
	 * @param int[]               $chunks
	 * @throws CancelException
	 */
	public function executeChunkedTask(ChunkedTask $task, GroupedChunkHandler $chunkHandler, EditTaskHandler $editHandler, array $chunks): void
	{
		$constructors = iterator_to_array($task->prepareConstructors($editHandler), false);
		$left = $total = $chunkHandler->requestAll($chunks, $constructors);
		while (true) {
			EditThread::getInstance()->checkExecution();
			if (($key = $chunkHandler->getNextChunk()) !== null) {
				$left--;

				foreach ($chunkHandler->getData() as $k => $information) {
					$editHandler->setChunk($k, $information);
				}

				HeightMapCache::prepare();

				foreach ($constructors as $constructor) {
					$constructor->moveTo($key);
				}

				EditThread::getInstance()->debug("Chunk " . $key . " was edited successful, " . $left . " chunks left");
				$this->postProgress(($total - $left) / $total);

				$editHandler->finish();
			}
			if ($left <= 0) {
				break;
			}
			if ($chunkHandler->getNextChunk() === null) {
				EditThread::getInstance()->waitForData();
			} else {
				EditThread::getInstance()->parseInput();
			}
		}
	}
}