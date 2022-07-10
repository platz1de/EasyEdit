<?php

namespace platz1de\EasyEdit\task\schematic;

use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\handler\EditHandler;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\schematic\SchematicFileAdapter;
use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\session\SessionIdentifier;
use platz1de\EasyEdit\session\SessionManager;
use platz1de\EasyEdit\task\ExecutableTask;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\thread\output\session\MessageSendData;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\MixedUtils;

class SchematicSaveTask extends ExecutableTask
{
	private string $schematicPath;
	private StoredSelectionIdentifier $saveId;

	/**
	 * @param StoredSelectionIdentifier $saveId
	 * @param string                    $schematicPath
	 * @return SchematicSaveTask
	 */
	public static function from(StoredSelectionIdentifier $saveId, string $schematicPath): SchematicSaveTask
	{
		$instance = new self();
		$instance->schematicPath = $schematicPath;
		$instance->saveId = $saveId;
		return $instance;
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "schematic_save";
	}

	public function execute(): void
	{
		$start = microtime(true);
		$selection = StorageModule::mustGetDynamic($this->saveId);
		SchematicFileAdapter::createFromSelection($this->schematicPath, $selection);
		$this->sendOutputPacket(new MessageSendData($this->getTaskId(), Messages::replace("schematic-created", ["{time}" => (string) round(microtime(true) - $start, 2), "{changed}" => MixedUtils::humanReadable($selection->getIterator()->getReadBlockCount()), "{name}" => basename($this->schematicPath)])));
	}

	public function getProgress(): float
	{
		return 0; //No splitting
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