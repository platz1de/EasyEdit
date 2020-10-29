<?php

namespace platz1de\EasyEdit\task;

use platz1de\EasyEdit\worker\EditWorker;
use platz1de\EasyEdit\worker\WorkerAdapter;
use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use Threaded;
use ThreadedLogger;
use Throwable;

abstract class EditTask extends Threaded
{
	/**
	 * @var bool
	 */
	private $finished = false;

	/**
	 * @var EditWorker
	 */
	protected $worker;

	/**
	 * @var int
	 */
	private $id;

	/**
	 * @var string
	 */
	protected $chunkData;

	/**
	 * @var string
	 */
	private $result;

	/**
	 * EditTask constructor.
	 * @param Level $level
	 */
	public function __construct(Level $level)
	{
		$this->id = WorkerAdapter::getId();
		$this->chunkData = igbinary_serialize(array_map(static function (Chunk $chunk) {
			return $chunk->fastSerialize();
		}, static::getNeededChunks($level, $this)));
	}

	public function run(): void
	{
		$chunkManager = new ChunkManager();

		foreach (array_map(static function (string $chunk) {
			return Chunk::fastDeserialize($chunk);
		}, igbinary_unserialize($this->chunkData)) as $chunk) {
			$chunkManager->setChunk($chunk->getX(), $chunk->getZ(), $chunk);
		}

		$this->getLogger()->debug("Task " . $this->getTaskName() . ":" . $this->getId() . " loaded " . count($chunkManager->getChunks()) . " Chunks");


		$this->getLogger()->debug("Running Task " . $this->getTaskName() . ":" . $this->getId());

		try {
			$this->execute($chunkManager);
			$this->getLogger()->debug("Task " . $this->getTaskName() . ":" . $this->getId() . " was executed successful");

			$this->result = igbinary_serialize(array_map(static function (Chunk $chunk) {
				return $chunk->fastSerialize();
			}, $chunkManager->getChunks()));
		} catch (Throwable $exception) {
			$this->getLogger()->logException($exception);
		}
		$this->finished = true;
	}

	/**
	 * @return ThreadedLogger
	 */
	public function getLogger(): ThreadedLogger
	{
		return $this->worker->getLogger();
	}

	/**
	 * @return string
	 */
	abstract public function getTaskName(): string;

	abstract public function execute(ChunkManager $chunkManager): void;

	/**
	 * @param Level    $level
	 * @param EditTask $task
	 * @return Chunk[]
	 */
	abstract public static function getNeededChunks(Level $level, EditTask $task): array;

	/**
	 * @return int
	 */
	public function getId(): int
	{
		return $this->id;
	}

	/**
	 * @return bool
	 */
	public function isFinished(): bool
	{
		return $this->finished;
	}

	/**
	 * @return Chunk[]
	 */
	public function getResult(): array
	{
		if (isset($this->result)) {
			return array_map(static function (string $chunk) {
				return Chunk::fastDeserialize($chunk);
			}, igbinary_unserialize($this->result));
		}

		return [];
	}
}