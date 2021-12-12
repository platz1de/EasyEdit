<?php

namespace platz1de\EasyEdit\task\editing\selection;

use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\task\editing\EditTask;
use platz1de\EasyEdit\utils\AdditionalDataManager;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\math\Vector3;
use pocketmine\world\format\Chunk;
use pocketmine\world\World;

abstract class SelectionEditTask extends EditTask
{
	protected Selection $selection;
	protected Selection $current;
	private Vector3 $position;
	private Vector3 $splitOffset;
	private int $totalPieces;
	private int $piecesLeft;

	/**
	 * @param SelectionEditTask     $instance
	 * @param string                $world
	 * @param AdditionalDataManager $data
	 * @param Selection             $selection
	 * @param Vector3               $position
	 * @param Vector3               $splitOffset
	 * @return void
	 */
	public static function initSelection(SelectionEditTask $instance, string $world, AdditionalDataManager $data, Selection $selection, Vector3 $position, Vector3 $splitOffset): void
	{
		EditTask::initEditTask($instance, $world, $data);
		$instance->selection = $selection;
		$instance->position = $position;
		$instance->splitOffset = $splitOffset;
	}

	public function execute(): void
	{
		$pieces = $this->selection->split($this->splitOffset);
		$this->totalPieces = count($pieces);
		$this->piecesLeft = count($pieces);
		foreach ($pieces as $key => $piece) {
			if ($key === array_key_last($pieces)) {
				$this->getDataManager()->setFinal();
			}
			$this->current = $piece;
			if ($this->requestChunks($piece->getNeededChunks($this->position))) {
				$this->getDataManager()->donePiece();
				$this->piecesLeft--;
			} else {
				return; //task was cancelled
			}
		}
	}

	/**
	 * @param Chunk[] $chunks
	 * @return Chunk[]
	 */
	public function filterChunks(array $chunks): array
	{
		foreach ($chunks as $hash => $chunk) {
			World::getXZ($hash, $x, $z);
			//separate chunks which are only loaded for patterns
			if (!$this->current->isChunkOfSelection($x, $z, $this->position)) {
				unset($chunks[$hash]);
			}
		}
		return $chunks;
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);
		$stream->putString($this->selection->fastSerialize());
		$stream->putVector($this->position);
		$stream->putVector($this->splitOffset);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);
		$this->selection = Selection::fastDeserialize($stream->getString());
		$this->position = $stream->getVector();
		$this->splitOffset = $stream->getVector();
	}

	/**
	 * @return Selection
	 */
	public function getCurrentSelection(): Selection
	{
		return $this->current;
	}

	/**
	 * @return Selection
	 */
	public function getTotalSelection(): Selection
	{
		return $this->selection;
	}

	/**
	 * @return Vector3
	 */
	public function getPosition(): Vector3
	{
		return $this->position;
	}

	/**
	 * @return int
	 */
	public function getTotalPieces(): int
	{
		return $this->totalPieces;
	}

	/**
	 * @return int
	 */
	public function getPiecesLeft(): int
	{
		return $this->piecesLeft;
	}

	public function getProgress(): float
	{
		return ($this->totalPieces - $this->piecesLeft) / $this->totalPieces;
	}
}