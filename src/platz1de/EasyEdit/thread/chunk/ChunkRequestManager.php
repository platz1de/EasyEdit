<?php

namespace platz1de\EasyEdit\thread\chunk;

use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\thread\output\ChunkRequestData;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\world\ChunkInformation;
use pocketmine\world\World;

class ChunkRequestManager
{
	private const MAX_REQUEST = 16; //max concurrent requests
	/**
	 * @var ChunkRequest[]
	 */
	private static array $queue = [];
	private static int $currentRequests = 0;
	private static ?ChunkHandler $handler;

	public static function setHandler(ChunkHandler $handler): void
	{
		self::$handler = $handler;
	}

	public static function addRequest(ChunkRequest $request): void
	{
		if (self::$currentRequests >= self::MAX_REQUEST) {
			self::$queue[] = $request;
			return;
		}
		EditThread::getInstance()->sendOutput(new ChunkRequestData($request));
		self::$currentRequests++;
	}

	public static function handleInput(string $data): void
	{
		if (self::$handler === null) {
			EditThread::getInstance()->debug("Received unexpected chunk data, probably a cancelled request");
			return;
		}
		$stream = new ExtendedBinaryStream($data);
		$chunks = [];
		while (!$stream->feof()) {
			$chunks[World::chunkHash($stream->getInt(), $stream->getInt())] = ChunkInformation::readFrom($stream);
		}
		self::$handler->handleInput($chunks);
	}

	public static function markAsDone(): void
	{
		self::$currentRequests--;
		if (self::$currentRequests < self::MAX_REQUEST && self::$queue !== []) {
			self::addRequest(array_shift(self::$queue));
		}
	}

	public static function clear(): void
	{
		if (self::$handler === null) {
			return;
		}
		self::$handler->clear();
		self::$handler = null;
		self::$queue = [];
		self::$currentRequests = 0;
	}
}