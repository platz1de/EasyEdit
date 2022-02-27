<?php

namespace platz1de\EasyEdit\task;

use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\task\editing\selection\DynamicPasteTask;
use platz1de\EasyEdit\thread\input\TaskInputData;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\utils\AdditionalDataManager;
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
	 * @param string                    $owner
	 * @param StoredSelectionIdentifier $saveId
	 * @param string                    $world
	 * @param Vector3                   $position
	 * @param bool                      $keep
	 * @param bool                      $insert
	 * @return DynamicStoredPasteTask
	 */
	public static function from(string $owner, StoredSelectionIdentifier $saveId, string $world, Vector3 $position, bool $keep, bool $insert = false): DynamicStoredPasteTask
	{
		$instance = new self($owner);
		$instance->saveId = $saveId;
		$instance->world = $world;
		$instance->position = $position;
		$instance->keep = $keep;
		$instance->insert = $insert;
		return $instance;
	}

	/**
	 * @param string                    $owner
	 * @param StoredSelectionIdentifier $id
	 * @param Position                  $place
	 * @param bool                      $keep
	 * @param bool                      $insert
	 */
	public static function queue(string $owner, StoredSelectionIdentifier $id, Position $place, bool $keep, bool $insert = false): void
	{
		TaskInputData::fromTask(self::from($owner, $id, $place->getWorld()->getFolderName(), $place->asVector3(), $keep, $insert));
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
		$this->executor = DynamicPasteTask::from($this->getOwner(), $this->world, new AdditionalDataManager(true, true), $selection, $this->position, $this->position, $this->insert);
		$this->executor->execute();
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