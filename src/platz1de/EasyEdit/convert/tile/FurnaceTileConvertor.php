<?php

namespace platz1de\EasyEdit\convert\tile;

use pocketmine\block\tile\Furnace;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\nbt\tag\CompoundTag;

class FurnaceTileConvertor extends ContainerTileConvertor
{
	private const JAVA_RECIPES_USED = "RecipesUsed";
	private const JAVA_MAX_TIME = "CookTimeTotal";

	private const TAG_STORED_XP = "StoredXPInt";
	private const TAG_MAX_TIME_2 = "BurnDuration";

	public function toBedrock(CompoundTag $tile): void
	{
		parent::toBedrock($tile);
		//We could calculate xp from recipes used, but it's not worth it
		$tile->removeTag(self::JAVA_RECIPES_USED);
		$tile->setInt(self::TAG_STORED_XP, 0);
		//No idea where pmmp got "MaxTime" from
		$tile->setShort(Furnace::TAG_MAX_TIME, $tile->getShort(self::JAVA_MAX_TIME, 0));
		$tile->setShort(self::TAG_MAX_TIME_2, $tile->getShort(self::JAVA_MAX_TIME, 0));
		$tile->removeTag(self::JAVA_MAX_TIME);
	}

	public function toJava(CompoundTag $tile, BlockStateData $state): ?BlockStateData
	{
		parent::toJava($tile, $state);
		$tile->setShort(self::JAVA_MAX_TIME, $tile->getShort(Furnace::TAG_MAX_TIME, 0));
		$tile->removeTag(self::TAG_STORED_XP, self::TAG_MAX_TIME_2, Furnace::TAG_MAX_TIME);
		$tile->setTag(self::JAVA_RECIPES_USED, new CompoundTag());
		return null;
	}
}