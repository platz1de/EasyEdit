<?php

namespace platz1de\EasyEdit\task;

use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Selection;
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
	 * QueuedTask constructor.
	 * @param Selection $selection
	 * @param Pattern   $pattern
	 * @param Position  $place
	 * @param string    $task
	 */
	public function __construct(Selection $selection, Pattern $pattern, Position $place, string $task)
	{
		$this->selection = $selection;
		$this->pattern = $pattern;
		$this->place = $place;
		$this->task = $task;
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
}