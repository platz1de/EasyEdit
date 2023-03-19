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
	private string $world;
	private Vector3 $position;
	private DynamicPasteTask $executor;

	/**
	 * @param StoredSelectionIdentifier $saveId
	 * @param Position                  $position
	 * @param bool                      $keep
	 * @param int                       $mode
	 */
	public function __construct(private StoredSelectionIdentifier $saveId, Position $position, private bool $keep, private int $mode = DynamicPasteTask::MODE_REPLACE_ALL)
	{
		$this->world = $position->getWorld()->getFolderName();
		$this->position = $position->asVector3();
		parent::__construct();
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
		$this->executor = new DynamicPasteTask($this->world, $selection, $this->position, $this->mode);
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
		$stream->putInt($this->mode);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->saveId = StoredSelectionIdentifier::fastDeserialize($stream->getString());
		$this->world = $stream->getString();
		$this->position = $stream->getVector();
		$this->keep = $stream->getBool();
		$this->mode = $stream->getInt();
	}
}