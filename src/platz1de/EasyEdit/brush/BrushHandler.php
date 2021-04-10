<?php

namespace platz1de\EasyEdit\brush;

use platz1de\EasyEdit\pattern\Naturalize;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\pattern\Smooth;
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
		$target = $player->getTargetBlock(100);
		if ($target !== null) {
			switch (self::nameToIdentifier($brush->getString("brushType", -1, true))) {
				case 0:
					SetTask::queue(new Sphere($player->getName(), $player->getLevelNonNull()->getName(), $target, $brush->getShort("brushSize", 0, true)), Pattern::parse($brush->getString("brushPattern", "stone", true)), $player->asPosition());
					break;
				case 1:
					SetTask::queue(new Sphere($player->getName(), $player->getLevelNonNull()->getName(), $target, $brush->getShort("brushSize", 0, true)), new Smooth([], []), $player->asPosition());
					break;
				case 2:
					SetTask::queue(new Sphere($player->getName(), $player->getLevelNonNull()->getName(), $target, $brush->getShort("brushSize", 0, true)), new Pattern([new Naturalize([Pattern::parse($brush->getString("topBlock", "grass", true)), Pattern::parse($brush->getString("middleBlock", "dirt", true)), Pattern::parse($brush->getString("bottomBlock", "stone", true))], [])], []), $player->asPosition());
			}
		}
	}

	/**
	 * @param string $brush
	 * @return int
	 */
	public static function nameToIdentifier(string $brush): int
	{
		switch (strtolower($brush)) {
			case "sphere":
			case "sph":
			case "sp":
				return 0;
			case "smooth":
			case "smoothing":
				return 1;
			case "naturalize":
			case "nat":
			case "naturalized":
				return 2;
		}
		return 0;
	}
}