<?php

namespace platz1de\EasyEdit\session;

use BadMethodCallException;
use platz1de\EasyEdit\command\exception\NoClipboardException;
use platz1de\EasyEdit\command\exception\NoSelectionException;
use platz1de\EasyEdit\command\exception\WrongSelectionTypeException;
use platz1de\EasyEdit\handler\EditHandler;
use platz1de\EasyEdit\math\BlockVector;
use platz1de\EasyEdit\result\EditTaskResult;
use platz1de\EasyEdit\result\TaskResult;
use platz1de\EasyEdit\result\TaskResultPromise;
use platz1de\EasyEdit\selection\Cube;
use platz1de\EasyEdit\selection\identifier\SelectionIdentifier;
use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\task\ExecutableTask;
use platz1de\EasyEdit\task\StaticStoredPasteTask;
use platz1de\EasyEdit\thread\input\task\CleanStorageTask;
use platz1de\EasyEdit\utils\MessageComponent;
use platz1de\EasyEdit\utils\MessageCompound;
use platz1de\EasyEdit\utils\Messages;
use platz1de\EasyEdit\utils\MixedUtils;
use platz1de\EasyEdit\world\clientblock\ClientSideBlockManager;
use platz1de\EasyEdit\world\clientblock\StructureBlockOutline;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use SplStack;

class Session
{
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

	public function __construct(private SessionIdentifier $id)
	{
		if (!$id->isPlayer()) {
			throw new BadMethodCallException("Session can only be created for players, plugins or internal use should use tasks directly");
		}
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
	 * @template T of TaskResult
	 * @param ExecutableTask<T> $task
	 * @return TaskResultPromise<T>
	 */
	public function runTask(ExecutableTask $task): TaskResultPromise
	{
		EditHandler::affiliateTask($this, $task->getTaskId());
		return $task->run()
			->onCancel(fn() => $this->sendMessage("task-cancelled-self"))
			->onFail(fn(string $message) => $this->sendMessage("task-crash", ["{message}" => $message]));
	}

	/**
	 * @param ExecutableTask<EditTaskResult> $task
	 */
	public function runSettingTask(ExecutableTask $task): void
	{
		$this->runEditTask("blocks-set", $task);
	}

	/**
	 * @param string                         $message
	 * @param ExecutableTask<EditTaskResult> $task
	 */
	public function runEditTask(string $message, ExecutableTask $task): void
	{
		$this->runTask($task)->then(function (EditTaskResult $result) use ($message) {
			$this->sendMessage($message, ["{time}" => $result->getFormattedTime(), "{changed}" => MixedUtils::humanReadable($result->getAffected())]);
			$this->addToHistory($result->getSelection());
		});
	}

	/**
	 * @param SelectionIdentifier $id
	 * @param bool                $fromUndo
	 */
	public function addToHistory(SelectionIdentifier $id): void
	{
		$id = $id->toIdentifier();
		if (!$id->isValid()) {
			return;
		}
		$this->past->unshift($id);
		if (!$this->future->isEmpty()) {
			CleanStorageTask::from(iterator_to_array($this->future, false));
			$this->future = new SplStack();
		}
	}

	/**
	 * @param SelectionIdentifier $id
	 */
	public function addToFuture(SelectionIdentifier $id): void
	{
		$id = $id->toIdentifier();
		if (!$id->isValid()) {
			return;
		}
		$this->future->unshift($id);
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
			$executor->runTask(new StaticStoredPasteTask($this->past->shift()->markForDeletion(), false))->then(function (EditTaskResult $result) {
				$this->sendMessage("blocks-pasted", ["{time}" => $result->getFormattedTime(), "{changed}" => MixedUtils::humanReadable($result->getAffected())]);
				$this->addToFuture($result->getSelection());
			});
		}
	}

	/**
	 * @param Session $executor
	 */
	public function redoStep(Session $executor): void
	{
		if ($this->canRedo()) {
			$executor->runEditTask("blocks-pasted", new StaticStoredPasteTask($this->future->shift()->markForDeletion(), false));
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
	 * @param SelectionIdentifier $id
	 */
	public function setClipboard(SelectionIdentifier $id): void
	{
		$id = $id->toIdentifier();
		if (!$id->isValid()) {
			return;
		}
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
		$this->selection->setPos1(BlockVector::fromVector($position));
		$this->updateSelectionHighlight();

		$this->sendMessage("selected-pos1", ["{x}" => (string) $position->getFloorX(), "{y}" => (string) $position->getFloorY(), "{z}" => (string) $position->getFloorZ()]);
	}

	/**
	 * @param Position $position
	 */
	public function selectPos2(Position $position): void
	{
		$this->createSelectionInWorld($position->getWorld()->getFolderName());
		$this->selection->setPos2(BlockVector::fromVector($position));
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
	 * @param string                                        $key
	 * @param string[]|MessageComponent[]|MessageCompound[] $arguments
	 * @return void
	 */
	public function sendMessage(string $key, array $arguments = []): void
	{
		Messages::send($this->getPlayer(), new MessageComponent($key, $arguments));
	}
}