<?php

namespace platz1de\EasyEdit\task\editing\selection;

use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
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
	protected SelectionContext $context;
	private int $totalPieces;
	private int $piecesLeft;

	/**
	 * @param Selection             $selection
	 * @param SelectionContext|null $context
	 */
	public function __construct(Selection $selection, ?SelectionContext $context = null)
	{
		$this->selection = $selection;
		$this->context = $context ?? SelectionContext::full();
		parent::__construct($selection->getWorldName());
	}

	public function execute(): void
	{
		StorageModule::checkFinished();
		$chunks = $this->orderChunks($this->selection->getNeededChunks());
		$this->totalPieces = count($chunks);
		$this->piecesLeft = count($chunks);
		$fastSet = VectorUtils::product($this->selection->getSize()) < ConfigManager::getFastSetMax();
		ChunkCollector::init($this->getWorld());
		foreach ($chunks as $chunk) {
			World::getXZ($chunk, $x, $z);
			$min = new Vector3($x << 4, World::Y_MIN, $z << 4);
			$max = new Vector3(($x << 4) + 15, World::Y_MAX, ($z << 4) + 15);
			if ($this->requestChunks([$chunk], $fastSet, $min, $max)) {
				$this->piecesLeft--;
			} else {
				return; //task was cancelled
			}
		}
		$this->finalize();
	}

	/**
	 * @param int[] $chunks
	 * @return int[]
	 */
	protected function orderChunks(array $chunks): array
	{
		return $chunks;
	}

	///**
	// * @return Closure
	// */
	//public function getCacheClosure(): Closure
	//{
	//	$selection = $this->selection;
	//	return static function (array $chunks) use ($selection): array {
	//		foreach ($chunks as $hash => $chunk) {
	//			World::getXZ($hash, $x, $z);
	//			if (!$selection->shouldBeCached($x, $z)) {
	//				unset($chunks[$hash]);
	//			}
	//		}
	//		return $chunks;
	//	};
	//}

	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);
		$stream->putString($this->selection->fastSerialize());
		$stream->putString($this->context->fastSerialize());
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);
		$this->selection = Selection::fastDeserialize($stream->getString());
		$this->context = SelectionContext::fastDeserialize($stream->getString());
	}

	/**
	 * @return Selection
	 */
	public function getSelection(): Selection
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