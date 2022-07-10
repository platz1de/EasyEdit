<?php

namespace platz1de\EasyEdit\session;

use BadMethodCallException;
use platz1de\EasyEdit\command\exception\NoClipboardException;
use platz1de\EasyEdit\command\exception\NoSelectionException;
use platz1de\EasyEdit\command\exception\WrongSelectionTypeException;
use platz1de\EasyEdit\handler\EditHandler;
use platz1de\EasyEdit\selection\Cube;
use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionManager;
use platz1de\EasyEdit\task\ExecutableTask;
use platz1de\EasyEdit\task\StaticStoredPasteTask;
use platz1de\EasyEdit\thread\input\task\CleanStorageTask;
use pocketmine\player\Player;
use pocketmine\Server;
use SplStack;
use Throwable;

class Session
{
	private SessionIdentifier $id;
	/**
	 * @var SplStack<StoredSelectionIdentifier>
	 */
	private SplStack $past;
	/**
	 * @var SplStack<StoredSelectionIdentifier>
	 */
	private SplStack $future;
	/**
	 * @var StoredSelectionIdentifier
	 */
	private StoredSelectionIdentifier $clipboard;

	public function __construct(SessionIdentifier $id)
	{
		if (!$id->isPlayer()) {
			throw new BadMethodCallException("Session can only be created for players, plugins or internal use should use tasks directly");
		}
		$this->id = $id;
		$this->past = new SplStack();
		$this->future = new SplStack();
		$this->clipboard = StoredSelectionIdentifier::invalid();
	}

	/**
	 * @return SessionIdentifier
	 */
	public function getIdentifier(): SessionIdentifier
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getPlayer(): string
	{
		return $this->id->getName();
	}

	/**
	 * @return Player
	 */
	public function asPlayer(): Player
	{
		$player = Server::getInstance()->getPlayerExact($this->getPlayer());
		if ($player === null) {
			throw new BadMethodCallException("Player is not online");
		}
		return $player;
	}

	/**
	 * @param ExecutableTask $task
	 */
	public function runTask(ExecutableTask $task): void
	{
		EditHandler::runPlayerTask($this, $task);
	}

	/**
	 * @param StoredSelectionIdentifier $id
	 * @param bool                      $fromUndo
	 * @return void
	 */
	public function addToHistory(StoredSelectionIdentifier $id, bool $fromUndo): void
	{
		if ($fromUndo) {
			$this->future->unshift($id);
		} else {
			$this->past->unshift($id);
			if (!$this->future->isEmpty()) {
				CleanStorageTask::from(iterator_to_array($this->future, false));
				$this->future = new SplStack();
			}
		}
	}

	/**
	 * @return bool
	 */
	public function canUndo(): bool
	{
		return !$this->past->isEmpty();
	}

	/**
	 * @return bool
	 */
	public function canRedo(): bool
	{
		return !$this->future->isEmpty();
	}

	/**
	 * @param Session $executor
	 */
	public function undoStep(Session $executor): void
	{
		if ($this->canUndo()) {
			$executor->runTask(StaticStoredPasteTask::from($this->past->shift(), false, true));
		}
	}

	/**
	 * @param Session $executor
	 */
	public function redoStep(Session $executor): void
	{
		if ($this->canRedo()) {
			$executor->runTask(StaticStoredPasteTask::from($this->future->shift(), false));
		}
	}

	/**
	 * @return StoredSelectionIdentifier
	 */
	public function getClipboard(): StoredSelectionIdentifier
	{
		if (!$this->clipboard->isValid()) {
			throw new NoClipboardException();
		}
		return $this->clipboard;
	}

	/**
	 * @param StoredSelectionIdentifier $id
	 */
	public function setClipboard(StoredSelectionIdentifier $id): void
	{
		if ($this->clipboard->isValid()) {
			CleanStorageTask::from([$this->clipboard]);
		}
		$this->clipboard = $id;
	}

	/**
	 * @return Selection
	 */
	public function getSelection(): Selection
	{
		try {
			$selection = SelectionManager::getFromPlayer($this->getPlayer());
		} catch (Throwable) {
			throw new NoSelectionException();
		}
		if (!$selection->isValid()) {
			throw new NoSelectionException();
		}
		return $selection;
	}

	/**
	 * @return Cube
	 */
	public function getCube(): Cube
	{
		$selection = $this->getSelection();
		if (!$selection instanceof Cube) {
			throw new WrongSelectionTypeException($selection::class, Cube::class);
		}
		return $selection;
	}
}