<?php

namespace platz1de\EasyEdit\task\editing\selection;

use Closure;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\task\editing\EditTask;
use platz1de\EasyEdit\thread\ChunkCollector;
use platz1de\EasyEdit\utils\ConfigManager;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\VectorUtils;
use platz1de\EasyEdit\world\ChunkInformation;
use pocketmine\math\Vector3;
use pocketmine\world\World;

abstract class SelectionEditTask extends EditTask
{
	protected Selection $selection;
	protected Selection $current;
	protected ?Vector3 $splitOffset = null;
	private int $totalPieces;
	private int $piecesLeft;

	public function execute(): void
	{
		$pieces = $this->selection->split($this->splitOffset ?? Vector3::zero());
		$this->totalPieces = count($pieces);
		$this->piecesLeft = count($pieces);
		if (VectorUtils::product($this->selection->getSize()) < ConfigManager::getFastSetMax()) {
			$this->getDataManager()->useFastSet();
		}
		ChunkCollector::init($this->getWorld());
		foreach ($pieces as $key => $piece) {
			if ($key === array_key_last($pieces)) {
				$this->getDataManager()->setFinal();
			}
			$this->current = $piece;
			$piece->init($this->getPosition());
			if ($this->requestChunks($piece->getNeededChunks())) {
				$this->getDataManager()->donePiece();
				$this->piecesLeft--;
			} else {
				return; //task was cancelled
			}
		}
		ChunkCollector::clear();
	}

	/**
	 * @param ChunkInformation[] $chunks
	 * @return ChunkInformation[]
	 */
	public function filterChunks(array $chunks): array
	{
		//TODO: Remove this once we properly support readonly and writeonly chunks
		foreach ($chunks as $hash => $chunk) {
			World::getXZ($hash, $x, $z);
			//separate chunks which are only loaded for patterns
			if (!$this->current->isChunkOfSelection($x, $z)) {
				unset($chunks[$hash]);
			}
		}
		return parent::filterChunks($chunks);
	}

	/**
	 * @return Closure
	 */
	public function getCacheClosure(): Closure
	{
		$selection = $this->current;
		return static function (array $chunks) use ($selection): array {
			foreach ($chunks as $hash => $chunk) {
				World::getXZ($hash, $x, $z);
				if (!$selection->shouldBeCached($x, $z)) {
					unset($chunks[$hash]);
				}
			}
			return $chunks;
		};
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);
		$stream->putString($this->selection->fastSerialize());
		$stream->putVector($this->splitOffset ?? Vector3::zero());
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);
		$this->selection = Selection::fastDeserialize($stream->getString());
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