<?php

namespace platz1de\EasyEdit\brush;

use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Sphere;
use platz1de\EasyEdit\task\selection\SetTask;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;


class BrushHandler
{
	/**
	 * @param CompoundTag $brush
	 * @param Player      $player
	 */
	public static function handleBrush(CompoundTag $brush, Player $player): void
	{
		$target = $player->getTargetBlock(50);
		if ($target !== null) {
			switch ($brush->getShort("brushType", -1, true)) {
				case 0:
					SetTask::queue(new Sphere($player->getName(), $player->getLevelNonNull()->getName(), $target, $brush->getShort("brushSize", 0, true)), Pattern::parse($brush->getString("brushPattern", "stone", true)), $player->asPosition());
			}
		}
	}

	/**
	 * @param string $brush
	 * @return int
	 */
	public static function nameToIdentifier(string $brush): int
	{
		switch ($brush){
			case "sphere":
			case "sph":
			case "sp":
				return 0;
		}
		return 0;
	}
}