<?php

namespace platz1de\EasyEdit\task;

use platz1de\EasyEdit\math\OffGridBlockVector;
use platz1de\EasyEdit\result\EditTaskResult;
use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\task\editing\DynamicPasteTask;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

/**
 * @extends ExecutableTask<EditTaskResult>
 */
class DynamicStoredPasteTask extends ExecutableTask
{
	private DynamicPasteTask $executor;

	/**
	 * @param StoredSelectionIdentifier $saveId
	 * @param string                    $world
	 * @param OffGridBlockVector        $position
	 * @param int                       $mode
	 */
	public function __construct(private StoredSelectionIdentifier $saveId, private string $world, private OffGridBlockVector $position, private int $mode = DynamicPasteTask::MODE_REPLACE_ALL)
	{
		parent::__construct();
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "dynamic_storage_paste";
	}

	public function executeInternal(): EditTaskResult
	{
		$this->executor = new DynamicPasteTask($this->world, $this->saveId, $this->position, $this->mode);
		return $this->executor->executeInternal();
	}

	public function attemptRecovery(): EditTaskResult
	{
		return $this->executor->attemptRecovery();
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->saveId->fastSerialize());
		$stream->putString($this->world);
		$stream->putBlockVector($this->position);
		$stream->putInt($this->mode);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->saveId = StoredSelectionIdentifier::fastDeserialize($stream->getString());
		$this->world = $stream->getString();
		$this->position = $stream->getOffGridBlockVector();
		$this->mode = $stream->getInt();
	}
}