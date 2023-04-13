<?php

namespace platz1de\EasyEdit\task\schematic;

use platz1de\EasyEdit\result\EditTaskResult;
use platz1de\EasyEdit\schematic\SchematicFileAdapter;
use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\task\ExecutableTask;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

/**
 * @extends ExecutableTask<EditTaskResult>
 */
class SchematicLoadTask extends ExecutableTask
{
	/**
	 * @param string $schematicPath
	 */
	public function __construct(private string $schematicPath)
	{
		parent::__construct();
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "schematic_load";
	}

	public function executeInternal(): EditTaskResult
	{
		$start = microtime(true);
		$selection = DynamicBlockListSelection::empty();
		SchematicFileAdapter::readIntoSelection($this->schematicPath, $selection);
		$changeId = StorageModule::store($selection);
		return new EditTaskResult($selection->getIterator()->getWrittenBlockCount(), microtime(true) - $start, $changeId);
	}

	public function attemptRecovery(): EditTaskResult
	{
		return new EditTaskResult(0, 0, StoredSelectionIdentifier::invalid());
	}

	public function getProgress(): float
	{
		return 0; //No splitting
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->schematicPath);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->schematicPath = $stream->getString();
	}
}