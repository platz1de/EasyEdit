<?php

namespace platz1de\EasyEdit\task\editing\selection;

use BadMethodCallException;
use Generator;
use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\EditTaskResultCache;
use platz1de\EasyEdit\task\editing\selection\pattern\SetTask;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\thread\output\session\ClipboardCacheData;
use platz1de\EasyEdit\thread\output\session\HistoryCacheData;
use platz1de\EasyEdit\thread\output\session\MessageSendData;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\MixedUtils;
use pocketmine\math\Vector3;
use RuntimeException;

class CutTask extends SelectionEditTask
{
	private Vector3 $position;

	private CopyTask $executor1;
	private SetTask $executor2;

	/**
	 * @param Selection $selection
	 * @param Vector3   $position
	 */
	public function __construct(Selection $selection, Vector3 $position)
	{
		$this->position = $position;
		parent::__construct($selection);
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "cut";
	}

	public function execute(): void
	{
		$this->executor1 = new CopyTask($this->selection, $this->position, $this->context);
		$this->executor1->executeAssociated($this, false);
		$this->sendOutputPacket(new ClipboardCacheData(StorageModule::finishCollecting()));
		$this->executor2 = new SetTask($this->selection, new StaticBlock(0), $this->context);
		$this->executor2->executeAssociated($this, false);
		$this->sendOutputPacket(new HistoryCacheData(StorageModule::finishCollecting(), false));
		$this->notifyUser((string) round($this->executor1->totalTime + $this->executor2->totalTime, 2), MixedUtils::humanReadable($this->executor1->totalBlocks + $this->executor2->totalBlocks));
	}

	/**
	 * @param string $time
	 * @param string $changed
	 */
	public function notifyUser(string $time, string $changed): void
	{
		$this->sendOutputPacket(new MessageSendData("blocks-cut", ["{time}" => $time, "{changed}" => $changed]));
	}

	public function getProgress(): float
	{
		return ($this->executor1->getProgress() + (isset($this->executor2) ? $this->executor2->getProgress() : 0)) / 2;
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putVector($this->position);
		parent::putData($stream);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->position = $stream->getVector();
		parent::parseData($stream);
	}

	//TODO: execute task with custom splitting (chunk-by-chunk instead of copying all and then deleting)
	public function prepareConstructors(EditTaskHandler $handler): Generator
	{
		throw new BadMethodCallException("Not implemented");
	}

	//TODO: execute task with custom splitting (chunk-by-chunk instead of copying all and then deleting)
	public function executeEdit(EditTaskHandler $handler, int $chunk): void
	{
		throw new BadMethodCallException("Not implemented");
	}

	public function getUndoBlockList(): BlockListSelection
	{
		throw new RuntimeException("Not implemented");
	}
}