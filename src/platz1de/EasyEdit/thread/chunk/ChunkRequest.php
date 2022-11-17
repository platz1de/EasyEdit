<?php

namespace platz1de\EasyEdit\thread\chunk;

use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\world\ReferencedWorldHolder;

class ChunkRequest
{
	use ReferencedWorldHolder;

	public const TYPE_NORMAL = 0; //loaded whenever possible (no special order)
	public const TYPE_PRIORITY = 1; //loaded when received (editing thread must go to sleep until chunk is loaded)

	private int $type;
	private int $chunk;
	private ?int $payload;

	/**
	 * @param string   $world
	 * @param int      $chunk
	 * @param int      $type
	 * @param int|null $payload
	 */
	public function __construct(string $world, int $chunk, int $type = self::TYPE_NORMAL, ?int $payload = null)
	{
		$this->world = $world;
		$this->chunk = $chunk;
		$this->type = $type;
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
	 * @return int
	 */
	public function getType(): int
	{
		return $this->type;
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
		$stream->putInt($this->chunk);
		$stream->putInt($this->type);
		$stream->putBool($this->payload !== null);
		if ($this->payload !== null) {
			$stream->putInt($this->payload);
		}
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 * @return ChunkRequest
	 */
	public static function readFrom(ExtendedBinaryStream $stream): ChunkRequest
	{
		return new self($stream->getString(), $stream->getInt(), $stream->getInt(), $stream->getBool() ? $stream->getInt() : null);
	}
}