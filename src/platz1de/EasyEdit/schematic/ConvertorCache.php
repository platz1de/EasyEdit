<?php

namespace platz1de\EasyEdit\schematic;

use pocketmine\nbt\tag\CompoundTag;

class ConvertorCache
{
	/**
	 * @var CompoundTag[][]
	 */
	private array $cache = [];

	public function set(string $type, int $id, CompoundTag $data): void
	{
		$this->cache[$type][$id] = $data;
	}

	public function clean(): void
	{
		$this->cache = [];
	}
}