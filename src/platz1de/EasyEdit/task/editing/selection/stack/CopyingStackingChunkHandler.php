<?php

namespace platz1de\EasyEdit\task\editing\selection\stack;

use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\task\editing\GroupedChunkHandler;
use platz1de\EasyEdit\thread\chunk\ChunkRequest;
use platz1de\EasyEdit\thread\chunk\ChunkRequestManager;
use platz1de\EasyEdit\utils\VectorUtils;
use platz1de\EasyEdit\world\ChunkInformation;
use pocketmine\math\Axis;
use pocketmine\world\World;
use UnexpectedValueException;

//TODO: Fix this
class CopyingStackingChunkHandler extends GroupedChunkHandler
{
	/**
	 * @var array<int, array<int, ChunkInformation>>
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
	private Selection $selection;
	private int $axis;
	private int $amount;

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
		$size = VectorUtils::getVectorAxis($this->selection->getSize(), $this->axis);
		World::getXZ($chunk, $x, $z);
		for ($i = 1; $i <= abs($this->amount); $i++) {
			$j = $this->amount > 0 ? $i : -$i;
			if ($this->axis === Axis::X) {
				$minX = $x + ($size * $j) >> 4;
				$maxX = $x + ($size * $j + 15) >> 4;
				$minZ = $maxZ = $z;
			} else {
				$minZ = $z + ($size * $j) >> 4;
				$maxZ = $z + ($size * $j + 15) >> 4;
				$minX = $maxX = $x;
			}
			$this->waiting[$chunk]++;
			ChunkRequestManager::addRequest(new ChunkRequest($this->world, World::chunkHash($minX, $minZ), ChunkRequest::TYPE_NORMAL, $chunk));
			if ($minX !== $maxX || $minZ !== $maxZ) {
				ChunkRequestManager::addRequest(new ChunkRequest($this->world, World::chunkHash($maxX, $maxZ), ChunkRequest::TYPE_NORMAL, $chunk));
				$this->waiting[$chunk]++;
			}
		}
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
			throw new UnexpectedValueException("Received chunk with invalid payload");
		}
		if ($chunk === $payload) {
			$this->executors[$chunk] = $data;
		} else {
			$this->groups[$payload][] = $data;
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
		foreach ($this->groups as $chunk => $group) {
			if (isset($this->executors[$chunk]) && count($group) > 0) {
				return array_key_first($group);
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
		foreach ($this->groups as $chunk => $group) {
			if (isset($this->executors[$chunk]) && count($group) > 0) {
				break;
			}
		}
		if (!isset($chunk)) {
			throw new UnexpectedValueException("No chunk available");
		}
		ChunkRequestManager::markAsDone();
		$ret = [$chunk => $this->executors[$chunk], $key => $this->groups[$chunk][$key]];
		unset($this->groups[$chunk][$key]);
		if (--$this->waiting[$chunk] === 0) {
			unset($this->groups[$chunk], $this->waiting[$chunk], $this->executors[$chunk]);
			ChunkRequestManager::markAsDone();
		}
		return $ret;
	}
}