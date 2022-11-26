<?php

namespace platz1de\EasyEdit\thread\chunk;

use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\world\ReferencedWorldHolder;

class ChunkRequest
{
	use ReferencedWorldHolder;

	private int $type;
	private int $chunk;
	private ?int $payload;

	/**
	 * @param string   $world
	 * @param int      $chunk
	 * @param int|null $payload
	 */
	public function __construct(string $world, int $chunk, ?int $payload = null)
	{
		$this->world = $world;
		$this->chunk = $chunk;
		$this->payload = $payload;
	}

	/**
	 * @return int
	 */
	public function getChunk(): int
	{
		return $this->chunk;
	}

	/**
	 * @return int|null
	 */
	public function getPayload(): ?int
	{
		return $this->payload;
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->getWorldName());
		$stream->putLong($this->chunk);
		$stream->putBool($this->payload !== null);
		if ($this->payload !== null) {
			$stream->putLong($this->payload);
		}
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 * @return ChunkRequest
	 */
	public static function readFrom(ExtendedBinaryStream $stream): ChunkRequest
	{
		return new self($stream->getString(), $stream->getLong(), $stream->getBool() ? $stream->getLong() : null);
	}
}