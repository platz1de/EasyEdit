<?php

namespace platz1de\EasyEdit\task\editing\selection;

use Generator;
use platz1de\EasyEdit\math\OffGridBlockVector;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use platz1de\EasyEdit\selection\NonSavingBlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\thread\output\session\ClipboardCacheData;
use platz1de\EasyEdit\thread\output\session\MessageSendData;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\MixedUtils;
use platz1de\EasyEdit\utils\TileUtils;

class CopyTask extends SelectionEditTask
{
	private DynamicBlockListSelection $result;

	/**
	 * @param Selection             $selection
	 * @param OffGridBlockVector    $position
	 * @param SelectionContext|null $context
	 */
	public function __construct(Selection $selection, private OffGridBlockVector $position, ?SelectionContext $context = null)
	{
		parent::__construct($selection, $context);
	}

	public function execute(): void
	{
		$handle = $this->useDefaultHandler();
		if (!$handle) {
			$this->result = DynamicBlockListSelection::fromWorldPositions($this->position, $this->selection->getPos1(), $this->selection->getPos2());
			parent::execute();
			$this->totalBlocks = $this->result->getBlockCount();
			return;
		}
		$this->executeAssociated($this, false); //this calls this method again, but without the default handler
		$this->sendOutputPacket(new ClipboardCacheData(StorageModule::store($this->result)));
		$this->notifyUser((string) round($this->totalTime, 2), MixedUtils::humanReadable($this->totalBlocks));
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "copy";
	}

	/**
	 * @return BlockListSelection
	 */
	public function createUndoBlockList(): BlockListSelection
	{
		return new NonSavingBlockListSelection();
	}

	/**
	 * @param string $time
	 * @param string $changed
	 */
	public function notifyUser(string $time, string $changed): void
	{
		$this->sendOutputPacket(new MessageSendData("blocks-copied", ["{time}" => $time, "{changed}" => $changed]));
	}

	/**
	 * @param EditTaskhandler $handler
	 * @return Generator<ShapeConstructor>
	 */
	public function prepareConstructors(EditTaskHandler $handler): Generator
	{
		$result = $this->result;
		$ox = $result->getWorldOffset()->x;
		$oy = $result->getWorldOffset()->y;
		$oz = $result->getWorldOffset()->z;
		yield from $this->selection->asShapeConstructors(function (int $x, int $y, int $z) use ($ox, $oy, $oz, $handler, $result): void {
			$result->addBlock($x - $ox, $y - $oy, $z - $oz, $handler->getBlock($x, $y, $z));
			$result->addTile(TileUtils::offsetCompound($handler->getTile($x, $y, $z), -$ox, -$oy, -$oz));
		}, $this->context);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putBlockVector($this->position);
		parent::putData($stream);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->position = $stream->getOffGridBlockVector();
		parent::parseData($stream);
	}

	/**
	 * @return DynamicBlockListSelection
	 */
	public function getResult(): DynamicBlockListSelection
	{
		return $this->result;
	}
}