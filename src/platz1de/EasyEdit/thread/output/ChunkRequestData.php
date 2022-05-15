<?php

namespace platz1de\EasyEdit\thread\output;

use platz1de\EasyEdit\thread\ChunkCollector;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\thread\input\ChunkInputData;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\LoaderManager;
use platz1de\EasyEdit\world\ChunkInformation;
use platz1de\EasyEdit\world\ReferencedWorldHolder;
use pocketmine\block\tile\Tile;
use pocketmine\world\format\io\ChunkData;
use pocketmine\world\World;
use UnexpectedValueException;

class ChunkRequestData extends OutputData
{
	use ReferencedWorldHolder;

	/**
	 * @var int[]
	 */
	private array $chunks = [];

	/**
	 * @param int[]  $chunks
	 * @param string $world
	 */
	public static function from(array $chunks, string $world): void
	{
		$data = new self();
		$data->chunks = $chunks;
		$data->world = $world;
		if ($world !== "") {
			$data->send();
		} else {
			ChunkCollector::collectInput(ChunkInputData::empty());
			EditThread::getInstance()->getLogger()->debug("Not sending chunk request due to unknown world");
		}
	}

	public function handle(): void
	{
		$this->prepareNextChunk($this->chunks, $this->getWorld(), new ExtendedBinaryStream());
	}

	/**
	 * @param int[]                $chunks
	 * @param World                $world
	 * @param ExtendedBinaryStream $data
	 */
	private function prepareNextChunk(array $chunks, World $world, ExtendedBinaryStream $data): void
	{
		World::getXZ((int) array_pop($chunks), $x, $z);

		$world->orderChunkPopulation($x, $z, null)->onCompletion(
			function () use ($data, $z, $x, $world, $chunks): void {
				$data->putInt($x);
				$data->putInt($z);
				$chunk = LoaderManager::getChunk($world, $x, $z);
				if ($chunk instanceof ChunkData) {
					$tiles = $chunk->getTileNBT();
					$chunk = $chunk->getChunk();
				} else {
					$tiles = array_map(static function (Tile $tile) {
						return $tile->saveNBT();
					}, $chunk->getTiles());
				}
				(new ChunkInformation($chunk, $tiles))->putData($data);

				if ($chunks === []) {
					ChunkInputData::from($data->getBuffer());
				} else {
					$this->prepareNextChunk($chunks, $world, $data);
				}
			},
			function () use ($x, $z): void {
				throw new UnexpectedValueException("Failed to prepare Chunk " . $x . " " . $z);
			}
		);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->world);

		$stream->putInt(count($this->chunks));
		foreach ($this->chunks as $chunk) {
			$stream->putLong($chunk);
		}
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->world = $stream->getString();

		$count = $stream->getInt();
		for ($i = 0; $i < $count; $i++) {
			$this->chunks[] = $stream->getLong();
		}
	}
}