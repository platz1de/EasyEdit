<?php

namespace platz1de\EasyEdit\convert\tile;

use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\nbt\tag\CompoundTag;

abstract class TileConvertorPiece
{
	protected string $bedrockName;
	protected string $javaName;

	public function __construct(string $bedrockName, ?string $javaName)
	{
		$this->bedrockName = $bedrockName;
		if ($javaName !== null) {
			$this->javaName = $javaName;
		}
	}

	abstract public function preprocessTileState(BlockStateData $state): ?CompoundTag;

	abstract public function toBedrock(CompoundTag $tile): void;

	abstract public function toJava(CompoundTag $tile, BlockStateData $state): ?BlockStateData;

	/**
	 * @return string[]
	 */
	public function getIdentifiers(): array
	{
		$names = [$this->bedrockName];
		if (isset($this->javaName)) {
			$names[] = $this->javaName;
		}
		return $names;
	}
}