<?php

namespace platz1de\EasyEdit\task\schematic;

use platz1de\EasyEdit\result\SelectionManipulationResult;
use platz1de\EasyEdit\schematic\SchematicFileAdapter;
use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\task\ExecutableTask;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

/**
 * @extends ExecutableTask<SelectionManipulationResult>
 */
class SchematicSaveTask extends ExecutableTask
{
	/**
	 * @param StoredSelectionIdentifier $saveId
	 * @param string                    $schematicPath
	 */
	public function __construct(private StoredSelectionIdentifier $saveId, private string $schematicPath)
	{
		parent::__construct();
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "schematic_save";
	}

	protected function executeInternal(): SelectionManipulationResult
	{
		$selection = StorageModule::mustGetDynamic($this->saveId);
		SchematicFileAdapter::createFromSelection($this->schematicPath, $selection);
		return new SelectionManipulationResult($selection->getIterator()->getReadBlockCount());
	}

	public function attemptRecovery(): SelectionManipulationResult
	{
		return new SelectionManipulationResult(0);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->schematicPath);
		$stream->putString($this->saveId->fastSerialize());
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->schematicPath = $stream->getString();
		$this->saveId = StoredSelectionIdentifier::fastDeserialize($stream->getString());
	}
}