<?php

namespace platz1de\EasyEdit\task\expanding;

use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\thread\chunk\ChunkHandler;
use platz1de\EasyEdit\thread\chunk\ChunkRequest;
use platz1de\EasyEdit\thread\chunk\ChunkRequestManager;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\thread\output\ResultingChunkData;
use platz1de\EasyEdit\thread\ThreadData;
use platz1de\EasyEdit\world\ChunkInformation;
use UnexpectedValueException;

class ManagedChunkHandler implements ChunkHandler
{
	private ?ChunkInformation $current = null;
	/**
	 * @var int[]
	 */
	private array $requests = [];
	/**
	 * @var bool[]
	 */
	private array $loaded = [];

	public function __construct(private EditTaskHandler $handler) {}

	public function request(int $chunk): bool
	{
		$manager = $this->handler->getOrigin()->getManager();
		try {
			$manager->getChunk($chunk);
			EditThread::getInstance()->debug("Requested chunk is already loaded");
			return true;
		} catch (UnexpectedValueException) {
		}
		ChunkRequestManager::addRequest(new ChunkRequest($manager->getWorldName(), $chunk));
		while ($this->current === null && ThreadData::canExecute() && EditThread::getInstance()->allowsExecution()) {
			EditThread::getInstance()->waitForData();
		}
		if ($this->current === null) {
			return false;
		}
		$manager->setChunk($chunk, $this->current);
		$this->handler->getResult()->setChunk($chunk, clone $this->current);
		$this->current = null;
		//TODO: Hack to prevent chunk cap
		//Currently expanding selections expand in every direction, which means that the chunk cap is reached very quickly
		ChunkRequestManager::markAsDone();
		return true;
	}

	public function handleInput(int $chunk, ChunkInformation $data, ?int $payload): void
	{
		$this->current = $data;
	}

	public function clear(): void
	{
		$this->handler->getOrigin()->getManager()->cleanChunks();
		$this->current = null;
	}

	/**
	 * @param int $chunk
	 * @return bool
	 */
	public function checkRuntimeChunk(int $chunk): bool
	{
		if (!isset($this->loaded[$chunk])) {
			$this->loaded[$chunk] = true;
			if (!$this->request($chunk)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * @param int $chunk
	 */
	public function registerRequestedChunks(int $chunk): void
	{
		if (!isset($this->requests[$chunk])) {
			$this->requests[$chunk] = 0;
		}
		$this->requests[$chunk]++;
	}

	/**
	 * @param EditTaskHandler $handler
	 * @param int             $chunk
	 */
	public function checkUnload(EditTaskHandler $handler, int $chunk): void
	{
		if (isset($this->requests[$chunk]) && --$this->requests[$chunk] <= 0) {
			unset($this->requests[$chunk], $this->loaded[$chunk]);

			EditThread::getInstance()->sendOutput(new ResultingChunkData($this->handler->getResult()->getWorldName(), [$chunk => $handler->getResult()->getChunk($chunk)], $handler->prepareInjectionData($chunk)));

			$this->handler->getOrigin()->getManager()->filterChunks(function (array $c) use ($chunk): array {
				unset($c[$chunk]);
				return $c;
			});
			$this->handler->getResult()->filterChunks(function (array $c) use ($chunk): array {
				unset($c[$chunk]);
				return $c;
			});
			ChunkRequestManager::markAsDone();
		}
	}
}