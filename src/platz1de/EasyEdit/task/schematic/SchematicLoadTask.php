<?php

namespace platz1de\EasyEdit\task\schematic;

use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\schematic\SchematicFileAdapter;
use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use platz1de\EasyEdit\session\SessionIdentifier;
use platz1de\EasyEdit\task\ExecutableTask;
use platz1de\EasyEdit\thread\input\TaskInputData;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\thread\output\ClipboardCacheData;
use platz1de\EasyEdit\thread\output\MessageSendData;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\MixedUtils;
use pocketmine\math\Vector3;

class SchematicLoadTask extends ExecutableTask
{
	private string $schematicPath;

	/**
	 * @param string $schematicPath
	 * @return SchematicLoadTask
	 */
	public static function from(string $schematicPath): SchematicLoadTask
	{
		$instance = new self();
		$instance->schematicPath = $schematicPath;
		return $instance;
	}

	/**
	 * @param SessionIdentifier $player
	 * @param string            $schematicName
	 */
	public static function queue(SessionIdentifier $player, string $schematicName): void
	{
		TaskInputData::fromTask($player, self::from(EasyEdit::getSchematicPath() . $schematicName));
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "schematic_load";
	}

	public function execute(SessionIdentifier $executor): void
	{
		$start = microtime(true);
		$selection = new DynamicBlockListSelection($executor->getName(), Vector3::zero(), Vector3::zero());
		SchematicFileAdapter::readIntoSelection($this->schematicPath, $selection);
		StorageModule::collect($selection);
		$changeId = StorageModule::finishCollecting();
		ClipboardCacheData::from($executor, $changeId);
		MessageSendData::from($executor, Messages::replace("blocks-copied", ["{time}" => (string) round(microtime(true) - $start, 2), "{changed}" => MixedUtils::humanReadable($selection->getIterator()->getWrittenBlockCount())]));
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