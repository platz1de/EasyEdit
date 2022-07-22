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
use platz1de\EasyEdit\task\ExecutableTask;
use platz1de\EasyEdit\task\StaticStoredPasteTask;
use platz1de\EasyEdit\thread\input\task\CleanStorageTask;
use platz1de\EasyEdit\utils\Messages;
use platz1de\EasyEdit\world\clientblock\ClientSideBlockManager;
use platz1de\EasyEdit\world\clientblock\StructureBlockOutline;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use SplStack;

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
	private StoredSelectionIdentifier $clipboard;
	private Selection $selection;
	private int $highlight = -1;

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
			$executor->runTask(new StaticStoredPasteTask($this->past->shift(), false, true));
		}
	}

	/**
	 * @param Session $executor
	 */
	public function redoStep(Session $executor): void
	{
		if ($this->canRedo()) {
			$executor->runTask(new StaticStoredPasteTask($this->future->shift(), false));
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
		if (!isset($this->selection) || !$this->selection->isValid()) {
			throw new NoSelectionException();
		}
		return $this->selection;
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

	/**
	 * @param Position $position
	 */
	public function selectPos1(Position $position): void
	{
		$this->createSelectionInWorld($position->getWorld()->getFolderName());
		$this->selection->setPos1($position->floor());
		$this->updateSelectionHighlight();

		$this->sendMessage("selected-pos1", ["{x}" => (string) $position->getFloorX(), "{y}" => (string) $position->getFloorY(), "{z}" => (string) $position->getFloorZ()]);
	}

	/**
	 * @param Position $position
	 */
	public function selectPos2(Position $position): void
	{
		$this->createSelectionInWorld($position->getWorld()->getFolderName());
		$this->selection->setPos2($position->floor());
		$this->updateSelectionHighlight();

		$this->sendMessage("selected-pos2", ["{x}" => (string) $position->getFloorX(), "{y}" => (string) $position->getFloorY(), "{z}" => (string) $position->getFloorZ()]);
	}

	/**
	 * @param string $world
	 */
	private function createSelectionInWorld(string $world): void
	{
		if (isset($this->selection) && $this->selection instanceof Cube && $this->selection->getWorldName() === $world) {
			return;
		}

		$this->selection = new Cube($world, null, null);
	}

	public function updateSelectionHighlight(): void
	{
		if ($this->highlight !== -1) {
			ClientSideBlockManager::unregisterBlock($this->getPlayer(), $this->highlight);
		}
		$this->highlight = -1;

		if ($this->selection->isValid()) {
			$this->highlight = ClientSideBlockManager::registerBlock($this->getPlayer(), new StructureBlockOutline($this->selection->getWorldName(), $this->selection->getPos1(), $this->selection->getPos2()));
		}
	}

	/**
	 * @param string   $key
	 * @param string[] $arguments
	 * @return void
	 */
	public function sendMessage(string $key, array $arguments = []): void
	{
		Messages::send($this->getPlayer(), $key, $arguments);
	}
}