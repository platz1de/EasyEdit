<?php

namespace platz1de\EasyEdit\task\editing\selection\stack;

use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\task\editing\GroupedChunkHandler;
use platz1de\EasyEdit\thread\chunk\ChunkRequest;
use platz1de\EasyEdit\thread\chunk\ChunkRequestManager;
use platz1de\EasyEdit\utils\MixedUtils;
use platz1de\EasyEdit\utils\VectorUtils;
use platz1de\EasyEdit\world\ChunkInformation;
use pocketmine\math\Axis;
use pocketmine\math\Vector2;
use pocketmine\world\World;
use UnexpectedValueException;

class CopyingStackingChunkHandler extends GroupedChunkHandler
{
	/**
	 * @var array<int, array<int, array{bool, ChunkInformation}>>
	 */
	private array $groups = [];
	/**
	 * @var int[]
	 */
	private array $waiting = [];
	/**
	 * @var ChunkInformation[]
	 */
	private array $executors = [];
	/**
	 * @var int[]
	 */
	private array $connections = [];
	private Selection $selection;
	private int $axis;
	private int $amount;
	private int $current;

	/**
	 * @param string    $world
	 * @param Selection $selection
	 * @param int       $axis
	 * @param int       $amount
	 */
	public function __construct(string $world, Selection $selection, int $axis, int $amount)
	{
		parent::__construct($world);
		$this->selection = $selection;
		$this->axis = $axis;
		$this->amount = $amount;
	}

	/**
	 * @param int $chunk
	 * @return true
	 */
	public function request(int $chunk): bool
	{
		$this->waiting[$chunk] = 0;
		$this->groups[$chunk] = [];
		ChunkRequestManager::addRequest(new ChunkRequest($this->world, $chunk, ChunkRequest::TYPE_NORMAL, $chunk));
		World::getXZ($chunk, $x, $z);
		$min = $this->selection->getCubicStart();
		$size = $this->selection->getSize();
		//We are guaranteed to have a size bigger than one chunk (at least 8 actually)
		if ($this->axis === Axis::X) {
			$offsetMin = MixedUtils::positiveModulo(($x << 4) - $min->x, $size->x);
			$offsetMax = MixedUtils::positiveModulo(($x << 4) - $min->x + 15, $size->x);
		} else {
			$offsetMin = MixedUtils::positiveModulo(($z << 4) - $min->z, $size->z);
			$offsetMax = MixedUtils::positiveModulo(($z << 4) - $min->z + 15, $size->z);
		}
		if ($offsetMin < $offsetMax) {
			$this->orderGroupChunk($chunk, new Vector2($x << 4, $z << 4), 15);
		} else {
			//Jump from end to start of origin
			$this->orderGroupChunk($chunk, new Vector2($x << 4, $z << 4), VectorUtils::getVectorAxis($size, $this->axis) - $offsetMin - 1);
			$this->orderGroupChunk($chunk, (new Vector2($x << 4, $z << 4))->add($this->axis === Axis::X ? $size->x - $offsetMin : 0, $this->axis === Axis::Z ? $size->z - $offsetMin : 0), $offsetMax);
		}
		return true;
	}

	/**
	 * @param int     $chunk
	 * @param Vector2 $start
	 * @param int     $offset
	 */
	private function orderGroupChunk(int $chunk, Vector2 $start, int $offset): void
	{
		$size = $this->selection->getSize();
		$min = $this->selection->getCubicStart();
		if ($this->axis === Axis::X) {
			$minX = ($min->x + MixedUtils::positiveModulo($start->x - $min->x, $size->x)) >> 4;
			$maxX = ($min->x + MixedUtils::positiveModulo($start->x + $offset - $min->x, $size->x)) >> 4;
			$minZ = $maxZ = $start->y >> 4;
		} else {
			$minZ = ($min->z + MixedUtils::positiveModulo($start->y - $min->z, $size->z)) >> 4;
			$maxZ = ($min->z + MixedUtils::positiveModulo($start->y + $offset - $min->z, $size->z)) >> 4;
			$minX = $maxX = $start->x >> 4;
		}
		if ($minX === $maxX && $minZ === $maxZ && World::chunkHash($minX, $minZ) === $chunk) {
			return;
		}
		$this->waiting[$chunk]++;
		if (isset($this->current) && $this->current === World::chunkHash($minX, $minZ)) {
			$this->connections[$chunk] = $this->current;
		} else {
			ChunkRequestManager::addRequest(new ChunkRequest($this->world, $this->current = World::chunkHash($minX, $minZ), ChunkRequest::TYPE_NORMAL, $chunk));
		}
		if ($minX !== $maxX || $minZ !== $maxZ) {
			ChunkRequestManager::addRequest(new ChunkRequest($this->world, $this->current = World::chunkHash($maxX, $maxZ), ChunkRequest::TYPE_NORMAL, $chunk));
			$this->waiting[$chunk]++;
		}
	}

	/**
	 * @param int              $chunk
	 * @param ChunkInformation $data
	 * @param int|null         $payload
	 */
	public function handleInput(int $chunk, ChunkInformation $data, ?int $payload): void
	{
		if ($payload === null) {
			throw new UnexpectedValueException("Received chunk with invalid payload");
		}
		if ($chunk === $payload) {
			$this->executors[$chunk] = $data;
		} else {
			$this->groups[$payload][$chunk] = [true, $data];
			$this->waiting[$payload]--;
			if ($this->waiting[$payload] === 0) {
				unset($this->waiting[$payload]);
			}
			if (in_array($chunk, $this->connections, true)) {
				$key = array_search($chunk, $this->connections, true);
				$this->groups[$key][$chunk] = [false, $data];
				$this->waiting[$key]--;
				if ($this->waiting[$key] === 0) {
					unset($this->waiting[$key]);
				}
				unset($this->connections[$key]);
			}
		}
	}

	public function clear(): void
	{
		$this->groups = [];
		$this->waiting = [];
		$this->executors = [];
	}

	/**
	 * @return int|null
	 */
	public function getNextChunk(): ?int
	{
		foreach ($this->executors as $chunk => $data) {
			if (!isset($this->waiting[$chunk])) {
				return $chunk;
			}
		}
		return null;
	}

	/**
	 * @return ChunkInformation[]
	 */
	public function getData(): array
	{
		if (($key = $this->getNextChunk()) === null) {
			throw new UnexpectedValueException("No chunk available");
		}
		ChunkRequestManager::markAsDone();
		$ret = [$key => $this->executors[$key]];
		foreach ($this->groups[$key] as $chunk => $data) {
			$ret[$chunk] = $data[1];
			if ($data[0]) {
				ChunkRequestManager::markAsDone();
			}
		}
		unset($this->executors[$key], $this->groups[$key]);
		return $ret;
	}
}