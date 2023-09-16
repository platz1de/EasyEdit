<?php

namespace platz1de\EasyEdit\task\expanding;

use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\task\CancelException;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\thread\chunk\ChunkHandler;
use platz1de\EasyEdit\thread\chunk\ChunkRequest;
use platz1de\EasyEdit\thread\EditThread;
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

	public function __construct(private EditTaskHandler $handler) { }

	/**
	 * @param int $chunk
	 * @throws CancelException
	 */
	public function request(int $chunk): void
	{
		$manager = $this->handler->getOrigin()->getManager();
		try {
			$manager->getChunk($chunk);
			EditThread::getInstance()->debug("Requested chunk is already loaded");
			return;
		} catch (UnexpectedValueException) {
		}
		EasyEdit::getEnv()->processChunkRequest(new ChunkRequest($manager->getWorldName(), $chunk), $this);
		while ($this->current === null) {
			EditThread::getInstance()->checkExecution();
			EditThread::getInstance()->waitForData();
		}
		/**
		 * PhpStan somehow expects null here (sadly doesn't seem to support the while loop above as a check)
		 * @phpstan-var ChunkInformation $c
		 */
		$c = $this->current;
		$manager->setChunk($chunk, $c);
		$this->handler->getResult()->setChunk($chunk, clone $c);
		$this->current = null;
		//TODO: Hack to prevent chunk cap
		//Currently expanding selections expand in every direction, which means that the chunk cap is reached very quickly
		EasyEdit::getEnv()->finalizeChunkStep();
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
	 * @throws CancelException
	 */
	public function checkRuntimeChunk(int $chunk): void
	{
		if (!isset($this->loaded[$chunk])) {
			$this->loaded[$chunk] = true;
			$this->request($chunk);
		}
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

			EasyEdit::getEnv()->submitSingleChunk($this->handler->getResult()->getWorldName(), $chunk, $handler->getResult()->getChunk($chunk), $handler->prepareInjectionData($chunk));

			$this->handler->getOrigin()->getManager()->filterChunks(function (array $c) use ($chunk): array {
				unset($c[$chunk]);
				return $c;
			});
			$this->handler->getResult()->filterChunks(function (array $c) use ($chunk): array {
				unset($c[$chunk]);
				return $c;
			});
			EasyEdit::getEnv()->finalizeChunkStep();
		}
	}
}