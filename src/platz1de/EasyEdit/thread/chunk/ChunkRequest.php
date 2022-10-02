<?php

namespace platz1de\EasyEdit\thread\chunk;

use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\world\ReferencedWorldHolder;

class ChunkRequest
{
	use ReferencedWorldHolder;

	public const TYPE_NORMAL = 0; //loaded whenever possible (no special order)
	public const TYPE_PRIORITY = 1; //loaded when received (editing thread must go to sleep until chunk is loaded)
	public const TYPE_SPECIAL = 2; //custom handler (e.g. for stacking)

	private int $type;
	private int $chunk;
	private string $customHandler;

	/**
	 * @param string $world
	 * @param int    $chunk
	 * @param int    $type
	 * @param string $customHandler
	 */
	public function __construct(string $world, int $chunk, int $type = self::TYPE_NORMAL, string $customHandler = "")
	{
		$this->world = $world;
		$this->chunk = $chunk;
		$this->type = $type;
		$this->customHandler = $customHandler;
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
	 * @return string|null
	 */
	public function getCustomHandler(): ?string
	{
		return $this->type === self::TYPE_SPECIAL ? $this->customHandler : null;
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->getWorldName());
		$stream->putInt($this->chunk);
		$stream->putInt($this->type);
		$stream->putString($this->customHandler);
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 * @return ChunkRequest
	 */
	public static function readFrom(ExtendedBinaryStream $stream): ChunkRequest
	{
		return new self($stream->getString(), $stream->getInt(), $stream->getInt(), $stream->getString());
	}
}