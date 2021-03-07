<?php

namespace platz1de\EasyEdit\task;

use Closure;
use platz1de\EasyEdit\history\HistoryManager;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\BlockListSelection;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\StaticBlockListSelection;
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
	 * @var array
	 */
	private $data;

	/**
	 * QueuedTask constructor.
	 * @param Selection    $selection
	 * @param Pattern      $pattern
	 * @param Position     $place
	 * @param string       $task
	 * @param array        $data
	 * @param Closure|null $finish
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function __construct(Selection $selection, Pattern $pattern, Position $place, string $task, array $data = [], ?Closure $finish = null)
	{
		$this->selection = $selection;
		$this->pattern = $pattern;
		$this->place = $place;
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
	 * @param string $key
	 * @return string|null
	 */
	public function getDataKeyed(string $key): ?string
	{
		return $this->data[$key] ?? null;
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