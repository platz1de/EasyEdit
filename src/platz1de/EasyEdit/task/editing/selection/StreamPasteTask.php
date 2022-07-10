<?php

namespace platz1de\EasyEdit\task\editing\selection;

use platz1de\EasyEdit\handler\EditHandler;
use platz1de\EasyEdit\selection\BinaryBlockListStream;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\session\SessionManager;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\type\PastingNotifier;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use pocketmine\math\Vector3;

class StreamPasteTask extends SelectionEditTask
{
	use PastingNotifier;

	/**
	 * @var BinaryBlockListStream
	 */
	protected Selection $current;

	/**
	 * @param string                $world
	 * @param AdditionalDataManager $data
	 * @param BinaryBlockListStream $selection
	 * @param Vector3               $position
	 * @param Vector3               $splitOffset
	 * @return StreamPasteTask
	 */
	public static function from(string $world, AdditionalDataManager $data, BinaryBlockListStream $selection, Vector3 $position, Vector3 $splitOffset): StreamPasteTask
	{
		$instance = new self($world, $data, $position);
		SelectionEditTask::initSelection($instance, $selection, $splitOffset);
		return $instance;
	}

	/**
	 * @param BinaryBlockListStream $selection
	 */
	public static function queue(BinaryBlockListStream $selection): void
	{
		EditHandler::runPlayerTask(SessionManager::get($selection->getPlayer()), self::from($selection->getWorldName(), new AdditionalDataManager(true, true), $selection, Vector3::zero(), Vector3::zero()));
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "stream_paste";
	}

	public function executeEdit(EditTaskHandler $handler): void
	{
		//WARNING: This isn't the default closure style
		$this->current->useOnBlocks(function (int $x, int $y, int $z, int $block) use ($handler): void {
			$handler->changeBlock($x, $y, $z, $block);
		}, SelectionContext::full(), $this->getTotalSelection());

		foreach ($this->current->getTiles() as $tile) {
			$handler->addTile($tile);
		}
	}


	/**
	 * @return BlockListSelection
	 */
	public function getUndoBlockList(): BlockListSelection
	{
		return new BinaryBlockListStream("undo", $this->getWorld());
	}
}