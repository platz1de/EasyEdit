<?php

namespace platz1de\EasyEdit\task;

use platz1de\EasyEdit\math\OffGridBlockVector;
use platz1de\EasyEdit\result\EditTaskResult;
use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\task\editing\selection\DynamicPasteTask;
use platz1de\EasyEdit\thread\modules\StorageModule;
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
	 * @param bool                      $keep
	 * @param int                       $mode
	 */
	public function __construct(private StoredSelectionIdentifier $saveId, private string $world, private OffGridBlockVector $position, private bool $keep, private int $mode = DynamicPasteTask::MODE_REPLACE_ALL)
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
		$selection = StorageModule::mustGetDynamic($this->saveId);
		if (!$this->keep) {
			StorageModule::cleanStored($this->saveId);
		}
		$this->executor = new DynamicPasteTask($this->world, $selection, $this->position, $this->mode);
		return $this->executor->executeInternal();
	}

	public function attemptRecovery(): EditTaskResult
	{
		return $this->executor->attemptRecovery();
	}

	public function getProgress(): float
	{
		return $this->executor->getProgress();
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->saveId->fastSerialize());
		$stream->putString($this->world);
		$stream->putBlockVector($this->position);
		$stream->putBool($this->keep);
		$stream->putInt($this->mode);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->saveId = StoredSelectionIdentifier::fastDeserialize($stream->getString());
		$this->world = $stream->getString();
		$this->position = $stream->getOffGridBlockVector();
		$this->keep = $stream->getBool();
		$this->mode = $stream->getInt();
	}
}