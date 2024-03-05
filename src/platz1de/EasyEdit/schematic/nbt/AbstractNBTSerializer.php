<?php

namespace platz1de\EasyEdit\schematic\nbt;

use pocketmine\nbt\NbtDataException;
use pocketmine\nbt\NbtStreamReader;
use pocketmine\nbt\TreeRoot;
use pocketmine\utils\BinaryDataException;

interface AbstractNBTSerializer extends NbtStreamReader
{
	/**
	 * @throws BinaryDataException
	 * @throws NbtDataException
	 */
	public function readFile(string $file): TreeRoot;

	public function readChunk(int $count): string;

	public function skip(int $count): void;

	public function close(): void;

	public function __clone(): void;

	public function optimizeHighFrequencyAccess(): void;
}