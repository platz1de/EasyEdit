<?php

namespace platz1de\EasyEdit\task;

use Closure;
use platz1de\EasyEdit\history\HistoryManager;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\StaticBlockListSelection;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use pocketmine\level\Position;

class QueuedTask
{
	/**
	 * @var Selection
	 */
	private $selection;
	/**
	 * @var Pattern
	 */
	private $pattern;
	/**
	 * @var Position
	 */
	private $place;
	/**
	 * @var string
	 */
	private $task;
	/**
	 * @var Closure
	 */
	private $finish;
	/**
	 * @var AdditionalDataManager
	 */
	private $data;

	/**
	 * QueuedTask constructor.
	 * @param Selection             $selection
	 * @param Pattern               $pattern
	 * @param Position              $place
	 * @param string                $task
	 * @param AdditionalDataManager $data
	 * @param Closure|null          $finish
	 */
	public function __construct(Selection $selection, Pattern $pattern, Position $place, string $task, AdditionalDataManager $data, ?Closure $finish = null)
	{
		$this->selection = $selection;
		$this->pattern = $pattern;
		$this->place = clone $place;
		$this->task = $task;
		if ($finish === null) {
			$finish = static function (Selection $selection, Position $place, StaticBlockListSelection $undo) {
				HistoryManager::addToHistory($selection->getPlayer(), $undo);
			};
		}
		$this->finish = $finish;
		$this->data = $data;
	}

	/**
	 * @return Selection
	 */
	public function getSelection(): Selection
	{
		return $this->selection;
	}

	/**
	 * @return Pattern
	 */
	public function getPattern(): Pattern
	{
		return $this->pattern;
	}

	/**
	 * @return Position
	 */
	public function getPlace(): Position
	{
		return $this->place;
	}

	/**
	 * @return string
	 */
	public function getTask(): string
	{
		return $this->task;
	}

	/**
	 * @return AdditionalDataManager
	 */
	public function getData(): AdditionalDataManager
	{
		return $this->data;
	}

	/**
	 * @param BlockListSelection $result
	 * @return void
	 */
	public function finishWith(BlockListSelection $result): void
	{
		$finish = $this->finish;
		$finish($this->getSelection(), $this->getPlace(), $result);
	}
}