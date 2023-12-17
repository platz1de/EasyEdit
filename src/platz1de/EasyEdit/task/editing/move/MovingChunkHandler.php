<?php

namespace platz1de\EasyEdit\task\editing\move;

use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\math\BlockOffsetVector;
use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\task\editing\GroupedChunkHandler;
use platz1de\EasyEdit\thread\chunk\ChunkRequest;
use platz1de\EasyEdit\world\ChunkInformation;
use pocketmine\Server;
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

	/**
	 * @param string            $world
	 * @param Selection         $selection
	 * @param BlockOffsetVector $direction
	 */
	public function __construct(string $world, private Selection $selection, private BlockOffsetVector $direction)
	{
		parent::__construct($world);
	}

	/**
	 * @param int $chunk
	 */
	public function request(int $chunk): void
	{
		EasyEdit::getEnv()->processChunkRequest(new ChunkRequest($this->world, $chunk, $chunk), $this);
		$this->queue[] = $chunk;
		$this->groupRequest[$chunk] = [$chunk => $chunk];
		$min = $this->selection->getPos1()->forceIntoChunkStart($chunk)->offset($this->direction);
		$max = $this->selection->getPos2()->forceIntoChunkEnd($chunk)->offset($this->direction);
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
				EasyEdit::getEnv()->processChunkRequest(new ChunkRequest($this->world, $c, $chunk), $this);
			}
		}
		$this->last = $chunk;
	}

	/**
	 * @param int                $chunk
	 * @param ShapeConstructor[] $constructors
	 * @return bool
	 */
	public function shouldRequest(int $chunk, array $constructors): bool
	{
		return true; //TODO: Add support for contexts
	}

	/**
	 * @param Selection $selection
	 * @return bool
	 */
	public function checkLoaded(Selection $selection): bool
	{
		$world = Server::getInstance()->getWorldManager()->getWorldByName($this->world);
		if ($world === null) {
			return false;
		}
		foreach ($selection->getNeededChunks() as $chunk) {
			World::getXZ($chunk, $x, $z);
			if (!$world->isChunkLoaded($x, $z)) {
				return false;
			}
		}
		$min = $this->selection->getPos1()->offset($this->direction);
		$max = $this->selection->getPos2()->offset($this->direction);
		for ($x = $min->x >> 4; $x <= $max->x >> 4; $x++) {
			for ($z = $min->z >> 4; $z <= $max->z >> 4; $z++) {
				if (!$world->isChunkLoaded($x, $z)) {
					return false;
				}
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
			EasyEdit::getEnv()->finalizeChunkStep();
		}
		unset($this->groupData[$key], $this->groupRequest[$key]);
		array_shift($this->queue);
		return $ret;
	}
}