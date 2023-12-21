<?php

namespace platz1de\EasyEdit\task\editing;

use Generator;
use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\result\CountingTaskResult;
use platz1de\EasyEdit\result\TaskResult;
use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
use platz1de\EasyEdit\selection\identifier\SelectionIdentifier;
use platz1de\EasyEdit\selection\identifier\SelectionSerializer;
use platz1de\EasyEdit\selection\NonSavingBlockListSelection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\task\ExecutableTask;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

/**
 * @extends ExecutableTask<CountingTaskResult>
 */
class CountTask extends ExecutableTask implements ChunkedTask
{
	/**
	 * @var int[]
	 */
	private array $counted = [];
	private SelectionContext $context;

	/**
	 * @param SelectionIdentifier   $selection
	 * @param SelectionContext|null $context
	 */
	public function __construct(private SelectionIdentifier $selection, ?SelectionContext $context = null)
	{
		$this->context = $context ?? SelectionContext::full();
		parent::__construct();
	}

	/**
	 * @return CountingTaskResult
	 */
	protected function executeInternal(): CountingTaskResult
	{
		$chunkHandler = new SingleChunkHandler($this->selection->asSelection()->getWorldName());
		EasyEdit::getEnv()->initChunkHandler($chunkHandler);
		$editHandler = new EditTaskHandler($this->selection->asSelection()->getWorldName(), new NonSavingBlockListSelection());
		EasyEdit::getEnv()->executeChunkedTask($this, $chunkHandler, $editHandler, $this->selection->asSelection()->getNeededChunks());

		EditThread::getInstance()->debug("Task " . $this->getTaskName() . ":" . $this->getTaskId() . " was executed successful, counting " . $editHandler->getReadBlockCount() . " blocks");
		return $this->toTaskResult();
	}

	protected function toTaskResult(): CountingTaskResult
	{
		return new CountingTaskResult($this->counted);
	}

	public function attemptRecovery(): TaskResult
	{
		return $this->toTaskResult();
	}

	public function calculateEffectiveComplexity(): int
	{
		return $this->selection->asSelection()->getSize()->volume();
	}

	public function canExecuteOnMainThread(): bool
	{
		return (new SingleChunkHandler($this->selection->asSelection()->getWorldName()))->checkLoaded($this->selection->asSelection());
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "count";
	}

	/**
	 * @param EditTaskHandler $handler
	 * @return Generator<ShapeConstructor>
	 */
	public function prepareConstructors(EditTaskHandler $handler): Generator
	{
		yield from $this->selection->asSelection()->asShapeConstructors(function (int $x, int $y, int $z) use ($handler): void {
			$id = $handler->getBlock($x, $y, $z);
			if (isset($this->counted[$id])) {
				$this->counted[$id]++;
			} else {
				$this->counted[$id] = 1;
			}
		}, $this->context);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString(SelectionSerializer::fastSerialize($this->selection));
		$stream->putString($this->context->fastSerialize());
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->selection = SelectionSerializer::fastDeserialize($stream->getString());
		$this->context = SelectionContext::fastDeserialize($stream->getString());
	}
}