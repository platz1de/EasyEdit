<?php

namespace platz1de\EasyEdit\task\editing\selection\move;

use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\task\editing\GroupedChunkHandler;
use platz1de\EasyEdit\thread\chunk\ChunkRequest;
use platz1de\EasyEdit\thread\chunk\ChunkRequestManager;
use platz1de\EasyEdit\utils\VectorUtils;
use platz1de\EasyEdit\world\ChunkInformation;
use pocketmine\math\Vector3;
use pocketmine\world\World;
use UnexpectedValueException;

class MovingChunkHandler extends GroupedChunkHandler
{
	/**
	 * @var int[]
	 */
	private array $queue = [];
	/**
	 * @var array<int, int[]>
	 */
	private array $groupRequest = [];
	/**
	 * @var array<int, array<int, ChunkInformation>>
	 */
	private array $groupData = [];
	/**
	 * @var array<int, int>
	 */
	private array $connections = [];
	private int $last;

	private Selection $selection;
	private Vector3 $direction;

	/**
	 * @param string    $world
	 * @param Selection $selection
	 * @param Vector3   $direction
	 */
	public function __construct(string $world, Selection $selection, Vector3 $direction)
	{
		parent::__construct($world);
		$this->selection = $selection;
		$this->direction = $direction;
	}

	/**
	 * @param int $chunk
	 * @return true
	 */
	public function request(int $chunk): bool
	{
		ChunkRequestManager::addRequest(new ChunkRequest($this->world, $chunk, $chunk));
		$this->queue[] = $chunk;
		$this->groupRequest[$chunk] = [$chunk => $chunk];
		$min = Vector3::maxComponents($this->selection->getCubicStart(), VectorUtils::getChunkPosition($chunk))->addVector($this->direction);
		$max = Vector3::minComponents($this->selection->getCubicEnd(), VectorUtils::getChunkPosition($chunk)->add(15, 0, 15))->addVector($this->direction);
		for ($x = $min->x >> 4; $x <= $max->x >> 4; $x++) {
			for ($z = $min->z >> 4; $z <= $max->z >> 4; $z++) {
				$c = World::chunkHash($x, $z);
				if ($c === $chunk) {
					continue;
				}
				if (isset($this->last) && in_array($c, $this->groupRequest[$this->last], true)) {
					$this->connections[$c] = $chunk;
					continue;
				}
				$this->groupRequest[$chunk][$c] = $c;
				ChunkRequestManager::addRequest(new ChunkRequest($this->world, $c, $chunk));
			}
		}
		$this->last = $chunk;
		return true;
	}

	/**
	 * @param int              $chunk
	 * @param ChunkInformation $data
	 * @param int|null         $payload
	 */
	public function handleInput(int $chunk, ChunkInformation $data, ?int $payload): void
	{
		if ($payload === null) {
			throw new UnexpectedValueException("Payload is null");
		}
		$this->groupData[$payload][$chunk] = $data;
		if (isset($this->connections[$chunk])) {
			$this->groupData[$this->connections[$chunk]][$chunk] = $data;
			unset($this->connections[$chunk]);
		}
	}

	public function clear(): void
	{
		$this->queue = [];
		$this->groupRequest = [];
		$this->groupData = [];
		$this->connections = [];
		unset($this->last);
	}

	/**
	 * @return int|null
	 */
	public function getNextChunk(): ?int
	{
		$key = $this->queue[0] ?? null;
		if ($key === null) {
			return null;
		}
		foreach ($this->groupRequest[$key] as $chunk) {
			if (!isset($this->groupData[$key][$chunk])) {
				return null;
			}
		}
		return $key;
	}

	/**
	 * @return ChunkInformation[]
	 */
	public function getData(): array
	{
		if (($key = $this->getNextChunk()) === null) {
			throw new UnexpectedValueException("No chunk available");
		}
		$ret = $this->groupData[$key];
		foreach ($this->groupRequest[$key] as $chunk) {
			ChunkRequestManager::markAsDone();
		}
		unset($this->groupData[$key], $this->groupRequest[$key]);
		array_shift($this->queue);
		return $ret;
	}
}