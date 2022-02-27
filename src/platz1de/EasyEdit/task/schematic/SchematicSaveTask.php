<?php

namespace platz1de\EasyEdit\task\schematic;

use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\schematic\SchematicFileAdapter;
use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\task\ExecutableTask;
use platz1de\EasyEdit\thread\input\TaskInputData;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\thread\output\MessageSendData;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\MixedUtils;

class SchematicSaveTask extends ExecutableTask
{
	private string $schematicPath;
	private StoredSelectionIdentifier $saveId;

	/**
	 * @param string                    $owner
	 * @param StoredSelectionIdentifier $saveId
	 * @param string                    $schematicPath
	 * @return SchematicSaveTask
	 */
	public static function from(string $owner, StoredSelectionIdentifier $saveId, string $schematicPath): SchematicSaveTask
	{
		$instance = new self($owner);
		$instance->schematicPath = $schematicPath;
		$instance->saveId = $saveId;
		return $instance;
	}

	/**
	 * @param string                    $player
	 * @param StoredSelectionIdentifier $saveId
	 * @param string                    $schematicName
	 */
	public static function queue(string $player, StoredSelectionIdentifier $saveId, string $schematicName): void
	{
		TaskInputData::fromTask(self::from($player, $saveId, EasyEdit::getSchematicPath() . $schematicName));
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
		MessageSendData::from($this->getOwner(), Messages::replace("schematic-created", ["{time}" => (string) round(microtime(true) - $start, 2), "{changed}" => MixedUtils::humanReadable($selection->getIterator()->getReadBlockCount()), "{name}" => basename($this->schematicPath)]));
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