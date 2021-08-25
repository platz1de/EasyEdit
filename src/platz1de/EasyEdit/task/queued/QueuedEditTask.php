<?php

namespace platz1de\EasyEdit\task\queued;

use Closure;
use platz1de\EasyEdit\history\HistoryManager;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\StaticBlockListSelection;
use platz1de\EasyEdit\task\EditTaskResult;
use platz1de\EasyEdit\task\PieceManager;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use pocketmine\math\Vector3;
use pocketmine\utils\Utils;
use pocketmine\world\Position;

class QueuedEditTask implements QueuedTask
{
	private Selection $selection;
	private Pattern $pattern;
	private Position $place;
	private string $task;
	private Closure $finish;
	private AdditionalDataManager $data;
	private PieceManager $executor;
	private Vector3 $splitOffset;

	/**
	 * QueuedTask constructor.
	 * @param Selection             $selection
	 * @param Pattern               $pattern
	 * @param Position              $place
	 * @param string                $task
	 * @param AdditionalDataManager $data
	 * @param Vector3               $splitOffset
	 * @param Closure|null          $finish
	 */
	public function __construct(Selection $selection, Pattern $pattern, Position $place, string $task, AdditionalDataManager $data, Vector3 $splitOffset, ?Closure $finish = null)
	{
		$this->selection = $selection;
		$this->pattern = $pattern;
		$this->place = Position::fromObject($place->floor(), $place->getWorld());
		$this->task = $task;
		$this->data = $data;
		$this->splitOffset = $splitOffset->floor();

		if ($finish === null) {
			$finish = static function (EditTaskResult $result): void {
				/** @var StaticBlockListSelection $undo */
				$undo = $result->getUndo();
				HistoryManager::addToHistory($undo->getPlayer(), $undo);
			};
		}

		Utils::validateCallableSignature(static function (EditTaskResult $result): void { }, $finish);

		$this->finish = $finish;
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
	 * @param EditTaskResult $result
	 * @return void
	 */
	public function finishWith(EditTaskResult $result): void
	{
		$finish = $this->finish;
		$finish($result);
	}

	/**
	 * @return bool
	 */
	public function isInstant(): bool
	{
		return false;
	}

	public function execute(): void
	{
		$this->executor = new PieceManager($this);
		$this->executor->start();
	}

	public function continue(): bool
	{
		return $this->executor->continue();
	}

	/**
	 * @return PieceManager
	 */
	public function getExecutor(): PieceManager
	{
		return $this->executor;
	}

	/**
	 * @return Vector3
	 */
	public function getSplitOffset(): Vector3
	{
		return $this->splitOffset;
	}
}