<?php

namespace platz1de\EasyEdit\task\editing\stack;

use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\task\editing\GroupedChunkHandler;
use platz1de\EasyEdit\thread\chunk\ChunkRequest;
use platz1de\EasyEdit\thread\chunk\ChunkRequestManager;
use platz1de\EasyEdit\world\ChunkInformation;
use pocketmine\math\Axis;
use pocketmine\world\World;
use UnexpectedValueException;

class SimpleStackingChunkHandler extends GroupedChunkHandler
{
	/**
	 * @var array<int, array<int, ChunkInformation>>
	 */
	private array $source = [];
	/**
	 * @var array<int, array<int, ChunkInformation>>
	 */
	private array $chunks = [];
	/**
	 * @var int[]
	 */
	private array $waiting = [];
	/**
	 * @var int[]
	 */
	private array $sourceOrder = [];
	private int $current;

	/**
	 * @param string    $world
	 * @param Selection $selection
	 * @param int       $axis
	 */
	public function __construct(string $world, private Selection $selection, private int $axis)
	{
		parent::__construct($world);
	}

	/**
	 * @param int $chunk
	 */
	public function request(int $chunk): void
	{
		World::getXZ($chunk, $x, $z);
		if ($this->axis === Axis::X) {
			$current = $z;
		} else {
			$current = $x;
		}
		if (!isset($this->current) || $this->current !== $current) {
			$this->current = $current;
			$this->sourceOrder[$this->current] = 0;
			$this->waiting[$this->current] = 0;
			$min = $this->selection->getPos1();
			$max = $this->selection->getPos2();
			if ($this->axis === Axis::X) {
				$min->z = $max->z = $current << 4;
			} else {
				$min->x = $max->x = $current << 4;
			}
			for ($resX = $min->x >> 4; $resX <= $max->x >> 4; $resX++) {
				for ($resZ = $min->z >> 4; $resZ <= $max->z >> 4; $resZ++) {
					ChunkRequestManager::addRequest(new ChunkRequest($this->world, World::chunkHash($resX, $resZ)));
					$this->sourceOrder[$this->current]++;
				}
			}
		}
		$this->waiting[$this->current]++;
		ChunkRequestManager::addRequest(new ChunkRequest($this->world, $chunk, $this->current));
	}

	/**
	 * @param int                $chunk
	 * @param ShapeConstructor[] $constructors
	 * @return bool
	 */
	public function shouldRequest(int $chunk, array $constructors): bool
	{
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
			World::getXZ($chunk, $x, $z);
			if ($this->axis === Axis::X) {
				$payload = $z;
			} else {
				$payload = $x;
			}
			$this->source[$payload][$chunk] = $data;
			if (--$this->sourceOrder[$payload] === 0) {
				unset($this->sourceOrder[$payload]);
			}
		} else {
			$this->chunks[$payload][$chunk] = $data;
		}
	}

	public function clear(): void
	{
		$this->source = [];
		$this->chunks = [];
		$this->waiting = [];
		$this->sourceOrder = [];
	}

	/**
	 * @return int|null
	 */
	public function getNextChunk(): ?int
	{
		foreach ($this->chunks as $offset => $group) {
			if (!isset($this->sourceOrder[$offset])) {
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
		foreach ($this->chunks as $offset => $group) {
			if (!isset($this->sourceOrder[$offset])) {
				break;
			}
		}
		if (!isset($offset)) {
			throw new UnexpectedValueException("No chunk available");
		}
		ChunkRequestManager::markAsDone();
		$ret = $this->source[$offset] ?? [];
		$ret[$key] = $this->chunks[$offset][$key];
		unset($this->chunks[$offset][$key]);
		if (isset($this->waiting[$offset]) && --$this->waiting[$offset] === 0) {
			for ($i = count($this->source[$offset]) - 1; $i >= 0; $i--) {
				ChunkRequestManager::markAsDone();
			}
			unset($this->chunks[$offset], $this->waiting[$offset], $this->source[$offset]);
		}
		return $ret;
	}
}