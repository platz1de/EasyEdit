<?php

namespace platz1de\EasyEdit\task\editing\selection;

use platz1de\EasyEdit\handler\EditHandler;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\selection\StaticBlockListSelection;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\selection\cubic\CubicStaticUndo;
use platz1de\EasyEdit\task\editing\type\PastingNotifier;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use pocketmine\math\Vector3;

class StaticPasteTask extends SelectionEditTask
{
	use CubicStaticUndo;
	use PastingNotifier;

	/**
	 * @var StaticBlockListSelection
	 */
	protected Selection $current;

	/**
	 * @param string                   $world
	 * @param AdditionalDataManager    $data
	 * @param StaticBlockListSelection $selection
	 * @param Vector3                  $position
	 * @return StaticPasteTask
	 */
	public static function from(string $world, AdditionalDataManager $data, StaticBlockListSelection $selection, Vector3 $position): StaticPasteTask
	{
		$instance = new self($world, $data, $position);
		$instance->selection = $selection;
		return $instance;
	}

	/**
	 * @param Session                  $session
	 * @param StaticBlockListSelection $selection
	 */
	public static function queue(Session $session, StaticBlockListSelection $selection): void
	{
		EditHandler::runPlayerTask($session, self::from($selection->getWorldName(), new AdditionalDataManager(true, true), $selection, Vector3::zero()));
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "static_paste";
	}

	public function executeEdit(EditTaskHandler $handler): void
	{
		$selection = $this->current;
		$selection->useOnBlocks(function (int $x, int $y, int $z) use ($handler, $selection): void {
			$block = $selection->getIterator()->getBlock($x, $y, $z);
			if (Selection::processBlock($block)) {
				$handler->changeBlock($x, $y, $z, $block);
			}
		}, SelectionContext::full(), $this->getTotalSelection());

		foreach ($selection->getTiles() as $tile) {
			$handler->addTile($tile);
		}
	}
}