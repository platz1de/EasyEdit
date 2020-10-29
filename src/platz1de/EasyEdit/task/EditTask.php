<?php

namespace platz1de\EasyEdit\task;

use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\worker\EditWorker;
use platz1de\EasyEdit\worker\WorkerAdapter;
use pocketmine\level\format\Chunk;
use pocketmine\level\utils\SubChunkIteratorManager;
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
	 * @var string
	 */
	private $selection;

	/**
	 * EditTask constructor.
	 * @param Selection $selection
	 */
	public function __construct(Selection $selection)
	{
		$this->id = WorkerAdapter::getId();
		$this->chunkData = igbinary_serialize(array_map(static function (Chunk $chunk) {
			return $chunk->fastSerialize();
		}, $selection->getNeededChunks()));
		$this->selection = igbinary_serialize($selection);
	}

	public function run(): void
	{
		$start = microtime(true);
		$iterator = new SubChunkIteratorManager(new ChunkManager());
		$selection = igbinary_unserialize($this->selection);

		foreach (array_map(static function (string $chunk) {
			return Chunk::fastDeserialize($chunk);
		}, igbinary_unserialize($this->chunkData)) as $chunk) {
			$iterator->level->setChunk($chunk->getX(), $chunk->getZ(), $chunk);
		}

		$this->getLogger()->debug("Task " . $this->getTaskName() . ":" . $this->getId() . " loaded " . count($iterator->level->getChunks()) . " Chunks");


		$this->getLogger()->debug("Running Task " . $this->getTaskName() . ":" . $this->getId());

		try {
			$this->execute($iterator, $selection);
			$this->getLogger()->debug("Task " . $this->getTaskName() . ":" . $this->getId() . " was executed successful in " . (microtime(true) - $start) . "s");

			$this->result = igbinary_serialize(array_map(static function (Chunk $chunk) {
				return $chunk->fastSerialize();
			}, $iterator->level->getChunks()));
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

	/**
	 * @param SubChunkIteratorManager $chunkManager
	 * @param Selection               $selection
	 */
	abstract public function execute(SubChunkIteratorManager $chunkManager, Selection $selection): void;

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