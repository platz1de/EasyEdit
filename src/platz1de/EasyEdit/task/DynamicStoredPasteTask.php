<?php

namespace platz1de\EasyEdit\task;

use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\task\editing\selection\DynamicPasteTask;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\math\Vector3;
use pocketmine\world\Position;

class DynamicStoredPasteTask extends ExecutableTask
{
	private StoredSelectionIdentifier $saveId;
	private string $world;
	private Vector3 $position;
	private bool $keep;
	private bool $insert;
	private DynamicPasteTask $executor;

	/**
	 * @param StoredSelectionIdentifier $saveId
	 * @param Position                  $position
	 * @param bool                      $keep
	 * @param bool                      $insert
	 * @return DynamicStoredPasteTask
	 */
	public static function from(StoredSelectionIdentifier $saveId, Position $position, bool $keep, bool $insert = false): DynamicStoredPasteTask
	{
		$instance = new self();
		$instance->saveId = $saveId;
		$instance->world = $position->getWorld()->getFolderName();
		$instance->position = $position->asVector3();
		$instance->keep = $keep;
		$instance->insert = $insert;
		return $instance;
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "dynamic_storage_paste";
	}

	public function execute(): void
	{
		$selection = StorageModule::mustGetDynamic($this->saveId);
		if (!$this->keep) {
			StorageModule::cleanStored($this->saveId);
		}
		$this->executor = DynamicPasteTask::from($this->world, $selection, $this->position, $this->insert);
		$this->executor->executeAssociated($this);
	}

	public function getProgress(): float
	{
		return $this->executor->getProgress();
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->saveId->fastSerialize());
		$stream->putString($this->world);
		$stream->putVector($this->position);
		$stream->putBool($this->keep);
		$stream->putBool($this->insert);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->saveId = StoredSelectionIdentifier::fastDeserialize($stream->getString());
		$this->world = $stream->getString();
		$this->position = $stream->getVector();
		$this->keep = $stream->getBool();
		$this->insert = $stream->getBool();
	}
}