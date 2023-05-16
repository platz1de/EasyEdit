<?php

namespace platz1de\EasyEdit\convert\tile;

use pocketmine\block\tile\Tile;
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

	public function toBedrock(CompoundTag $tile): void
	{
		$tile->setString(Tile::TAG_ID, $this->javaName);
	}

	public function toJava(CompoundTag $tile, BlockStateData $state): ?BlockStateData
	{
		$tile->setString(Tile::TAG_ID, $this->bedrockName);
		return null;
	}

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

	public function hasJavaCounterpart(): bool
	{
		return true;
	}
}