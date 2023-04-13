<?php

namespace platz1de\EasyEdit\task;

use platz1de\EasyEdit\result\EditTaskResult;
use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\selection\StaticBlockListSelection;
use platz1de\EasyEdit\task\editing\selection\StaticPasteTask;
use platz1de\EasyEdit\task\editing\selection\StreamPasteTask;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

/**
 * @extends ExecutableTask<EditTaskResult>
 */
class StaticStoredPasteTask extends ExecutableTask
{
	private StaticPasteTask|StreamPasteTask $executor;

	/**
	 * @param StoredSelectionIdentifier $saveId
	 * @param bool                      $keep
	 */
	public function __construct(private StoredSelectionIdentifier $saveId, private bool $keep)
	{
		parent::__construct();
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "static_storage_paste";
	}

	public function executeInternal(): EditTaskResult
	{
		//TODO: remove this
		$selection = StorageModule::mustGetStatic($this->saveId);
		if (!$this->keep) {
			StorageModule::cleanStored($this->saveId);
		}

		if ($selection instanceof StaticBlockListSelection) {
			$this->executor = new StaticPasteTask($selection);
		} else {
			$this->executor = new StreamPasteTask($selection);
		}
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
		$stream->putBool($this->keep);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->saveId = StoredSelectionIdentifier::fastDeserialize($stream->getString());
		$this->keep = $stream->getBool();
	}
}