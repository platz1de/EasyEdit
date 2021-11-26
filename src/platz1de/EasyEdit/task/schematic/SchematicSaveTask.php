<?php

namespace platz1de\EasyEdit\task\schematic;

use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\schematic\SchematicFileAdapter;
use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use platz1de\EasyEdit\task\ExecutableTask;
use platz1de\EasyEdit\thread\input\TaskInputData;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\thread\output\MessageSendData;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\MixedUtils;
use UnexpectedValueException;

class SchematicSaveTask extends ExecutableTask
{
	private string $schematicPath;
	private int $saveId;

	/**
	 * @param string $owner
	 * @param int    $saveId
	 * @param string $schematicPath
	 * @return SchematicSaveTask
	 */
	public static function from(string $owner, int $saveId, string $schematicPath): SchematicSaveTask
	{
		$instance = new self($owner);
		$instance->schematicPath = $schematicPath;
		$instance->saveId = $saveId;
		return $instance;
	}

	/**
	 * @param string $player
	 * @param int    $saveId
	 * @param string $schematicName
	 */
	public static function queue(string $player, int $saveId, string $schematicName): void
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
		$selection = StorageModule::getStored($this->saveId);
		if (!$selection instanceof DynamicBlockListSelection) {
			throw new UnexpectedValueException("Storage at id " . $this->saveId . " contained " . get_class($selection) . " expected " . DynamicBlockListSelection::class);
		}
		SchematicFileAdapter::createFromSelection($this->schematicPath, $selection);
		MessageSendData::from($this->getOwner(), Messages::replace("schematic-created", ["{time}" => (string) round(microtime(true) - $start, 2), "{changed}" => MixedUtils::humanReadable($selection->getIterator()->getReadBlockCount()), "{name}" => basename($this->schematicPath)]));
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->schematicPath);
		$stream->putInt($this->saveId);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->schematicPath = $stream->getString();
		$this->saveId = $stream->getInt();
	}
}