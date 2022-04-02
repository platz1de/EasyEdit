<?php

namespace platz1de\EasyEdit\task\editing\selection;

use platz1de\EasyEdit\selection\BinaryBlockListStream;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\type\PastingNotifier;
use platz1de\EasyEdit\thread\input\TaskInputData;
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
	 * @param string                $owner
	 * @param string                $world
	 * @param AdditionalDataManager $data
	 * @param BinaryBlockListStream $selection
	 * @param Vector3               $position
	 * @param Vector3               $splitOffset
	 * @return StreamPasteTask
	 */
	public static function from(string $owner, string $world, AdditionalDataManager $data, BinaryBlockListStream $selection, Vector3 $position, Vector3 $splitOffset): StreamPasteTask
	{
		$instance = new self($owner, $world, $data, $position);
		SelectionEditTask::initSelection($instance, $selection, $splitOffset);
		return $instance;
	}

	/**
	 * @param BinaryBlockListStream $selection
	 */
	public static function queue(BinaryBlockListStream $selection): void
	{
		TaskInputData::fromTask(self::from($selection->getPlayer(), $selection->getWorldName(), new AdditionalDataManager(true, true), $selection, Vector3::zero(), Vector3::zero()));
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

	public function getUndoBlockList(): BlockListSelection
	{
		return new BinaryBlockListStream($this->getOwner(), $this->getWorld());
	}
}