<?php

namespace platz1de\EasyEdit\task\schematic;

use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\schematic\SchematicFileAdapter;
use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use platz1de\EasyEdit\task\ExecutableTask;
use platz1de\EasyEdit\thread\input\TaskInputData;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\thread\output\ClipboardCacheData;
use platz1de\EasyEdit\thread\output\MessageSendData;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class SchematicLoadTask extends ExecutableTask
{
	private string $schematicPath;

	/**
	 * @param string $owner
	 * @param string $schematicPath
	 * @return SchematicLoadTask
	 */
	public static function from(string $owner, string $schematicPath): SchematicLoadTask
	{
		$instance = new self($owner);
		$instance->schematicPath = $schematicPath;
		return $instance;
	}

	/**
	 * @param string $player
	 * @param string $schematicName
	 */
	public static function queue(string $player, string $schematicName): void
	{
		TaskInputData::fromTask(self::from($player, EasyEdit::getSchematicPath() . $schematicName));
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "schematic_load";
	}

	public function execute(): void
	{
		$start = microtime(true);
		$selection = new DynamicBlockListSelection($this->getOwner());
		SchematicFileAdapter::readIntoSelection($this->schematicPath, $selection);
		StorageModule::collect($selection);
		$changeId = StorageModule::finishCollecting();
		ClipboardCacheData::from($this->getOwner(), $changeId);
		MessageSendData::from($this->getOwner(), Messages::replace("blocks-copied", ["{time}" => (string) (microtime(true) - $start), "{changed}" => (string) $selection->getIterator()->getWrittenBlockCount()]));
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