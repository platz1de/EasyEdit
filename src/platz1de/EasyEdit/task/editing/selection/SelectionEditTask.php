<?php

namespace platz1de\EasyEdit\task\editing\selection;

use Closure;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\task\editing\EditTask;
use platz1de\EasyEdit\thread\ChunkCollector;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\utils\ConfigManager;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\math\Vector3;
use pocketmine\world\World;

abstract class SelectionEditTask extends EditTask
{
	protected Selection $selection;
	protected Selection $current;
	private int $totalPieces;
	private int $piecesLeft;

	/**
	 * @param Selection $selection
	 */
	public function __construct(Selection $selection)
	{
		$this->selection = $selection;
		parent::__construct($selection->getWorldName());
	}

	public function execute(): void
	{
		StorageModule::checkFinished();
		$chunks = $this->selection->getNeededChunks();
		$this->totalPieces = count($chunks);
		$this->piecesLeft = count($chunks);
		$fastSet = VectorUtils::product($this->selection->getSize()) < ConfigManager::getFastSetMax();
		ChunkCollector::init($this->getWorld());
		$this->current = $this->selection; //TODO: remove this
		foreach ($chunks as $chunk) {
			World::getXZ($chunk, $x, $z);
			$min = new Vector3($x << 4, World::Y_MIN, $z << 4);
			$max = new Vector3(($x << 4) + 15, World::Y_MAX, ($z << 4) + 15);
			$c = $this->selection->getReferencedChunks($min, $max);
			$c[] = $chunk;
			if ($this->requestChunks($c, $fastSet, $min, $max)) {
				$this->piecesLeft--;
			} else {
				return; //task was cancelled
			}
		}
		$this->finalize();
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
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);
		$this->selection = Selection::fastDeserialize($stream->getString());
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