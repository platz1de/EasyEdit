<?php

namespace platz1de\EasyEdit\task;

use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\selection\StaticBlockListSelection;
use platz1de\EasyEdit\task\editing\selection\StaticPasteTask;
use platz1de\EasyEdit\task\editing\selection\StreamPasteTask;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\thread\output\session\HistoryCacheData;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\MixedUtils;

class StaticStoredPasteTask extends ExecutableTask
{
	private StoredSelectionIdentifier $saveId;
	private bool $keep;
	private bool $isUndo;
	private StaticPasteTask|StreamPasteTask $executor;

	/**
	 * @param StoredSelectionIdentifier $saveId
	 * @param bool                      $keep
	 * @param bool                      $isUndo
	 */
	public function __construct(StoredSelectionIdentifier $saveId, bool $keep, bool $isUndo = false)
	{
		$this->saveId = $saveId;
		$this->keep = $keep;
		$this->isUndo = $isUndo;
		parent::__construct();
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "static_storage_paste";
	}

	public function execute(): void
	{
		$selection = StorageModule::mustGetStatic($this->saveId);
		if (!$this->keep) {
			StorageModule::cleanStored($this->saveId);
		}

		if ($selection instanceof StaticBlockListSelection) {
			$this->executor = new StaticPasteTask($selection);
		} else {
			$this->executor = new StreamPasteTask($selection);
		}
		$this->executor->executeAssociated($this, false);

		$this->sendOutputPacket(new HistoryCacheData(StorageModule::finishCollecting(), $this->isUndo));
		$this->executor->notifyUser((string) round($this->executor->getTotalTime(), 2), MixedUtils::humanReadable($this->executor->getTotalBlocks()));
	}

	public function getProgress(): float
	{
		return $this->executor->getProgress();
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->saveId->fastSerialize());
		$stream->putBool($this->keep);
		$stream->putBool($this->isUndo);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->saveId = StoredSelectionIdentifier::fastDeserialize($stream->getString());
		$this->keep = $stream->getBool();
		$this->isUndo = $stream->getBool();
	}
}