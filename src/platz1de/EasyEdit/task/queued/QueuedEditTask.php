<?php

namespace platz1de\EasyEdit\task\queued;

use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\task\PieceManager;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\ReferencedWorldHolder;
use pocketmine\math\Vector3;

class QueuedEditTask implements QueuedTask
{
	use ReferencedWorldHolder;

	private Selection $selection;
	private Pattern $pattern;
	private Vector3 $place;
	private string $task;
	private AdditionalDataManager $data;
	private PieceManager $executor;
	private Vector3 $splitOffset;

	/**
	 * QueuedTask constructor.
	 * @param Selection             $selection
	 * @param Pattern               $pattern
	 * @param string                $world
	 * @param Vector3               $place
	 * @param string                $task
	 * @param AdditionalDataManager $data
	 * @param Vector3               $splitOffset
	 */
	public function __construct(Selection $selection, Pattern $pattern, string $world, Vector3 $place, string $task, AdditionalDataManager $data, Vector3 $splitOffset)
	{
		$this->selection = $selection;
		$this->pattern = $pattern;
		$this->world = $world;
		$this->place = $place->floor();
		$this->task = $task;
		$this->data = $data;
		$this->splitOffset = $splitOffset->floor();
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
	 * @return Vector3
	 */
	public function getPlace(): Vector3
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

	/**
	 * @return string
	 */
	public function fastSerialize(): string
	{
		$stream = new ExtendedBinaryStream();

		$stream->putString($this->selection->fastSerialize());
		$stream->putString($this->pattern->fastSerialize());
		$stream->putString($this->world);
		$stream->putVector($this->place);
		$stream->putString($this->task);
		$stream->putString(igbinary_serialize($this->data));
		$stream->putVector($this->splitOffset);

		return $stream->getBuffer();
	}

	/**
	 * @param string $data
	 * @return QueuedEditTask
	 */
	public static function fastDeserialize(string $data): QueuedEditTask
	{
		$stream = new ExtendedBinaryStream($data);

		return new QueuedEditTask(Selection::fastDeserialize($stream->getString()), Pattern::fastDeserialize($stream->getString()), $stream->getString(), $stream->getVector(), $stream->getString(), igbinary_unserialize($stream->getString()), $stream->getVector());
	}
}