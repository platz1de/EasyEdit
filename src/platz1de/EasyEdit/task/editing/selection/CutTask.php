<?php

namespace platz1de\EasyEdit\task\editing\selection;

use Generator;
use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\selection\cubic\CubicStaticUndo;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\thread\output\session\ClipboardCacheData;
use platz1de\EasyEdit\thread\output\session\HistoryCacheData;
use platz1de\EasyEdit\thread\output\session\MessageSendData;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\MixedUtils;
use platz1de\EasyEdit\utils\TileUtils;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Vector3;

class CutTask extends SelectionEditTask
{
	use CubicStaticUndo;

	private Vector3 $position;
	private DynamicBlockListSelection $result;

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
		$this->result = DynamicBlockListSelection::fromWorldPositions($this->position, $this->selection->getPos1(), $this->selection->getPos2());
		parent::execute();
		$this->sendOutputPacket(new ClipboardCacheData(StorageModule::store($this->result)));
		$this->sendOutputPacket(new HistoryCacheData(StorageModule::store($this->undo), false));
		$this->notifyUser((string) round($this->totalTime, 2), MixedUtils::humanReadable($this->totalBlocks));
	}

	/**
	 * @param EditTaskHandler $handler
	 * @return Generator<ShapeConstructor>
	 */
	public function prepareConstructors(EditTaskHandler $handler): Generator
	{
		$result = $this->result;
		$id = VanillaBlocks::AIR()->getStateId();
		$ox = $result->getWorldOffset()->getFloorX();
		$oy = $result->getWorldOffset()->getFloorY();
		$oz = $result->getWorldOffset()->getFloorZ();

		yield from $this->selection->asShapeConstructors(function (int $x, int $y, int $z) use ($id, $handler, $result, $ox, $oy, $oz): void {
			$result->addBlock($x - $ox, $y - $oy, $z - $oz, $handler->getBlock($x, $y, $z));
			$result->addTile(TileUtils::offsetCompound($handler->getTile($x, $y, $z), -$ox, -$oy, -$oz));
			$handler->changeBlock($x, $y, $z, $id);
		}, $this->context);
	}

	/**
	 * @param string $time
	 * @param string $changed
	 */
	public function notifyUser(string $time, string $changed): void
	{
		$this->sendOutputPacket(new MessageSendData("blocks-cut", ["{time}" => $time, "{changed}" => $changed]));
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
}