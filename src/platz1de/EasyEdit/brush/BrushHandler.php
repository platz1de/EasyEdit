<?php

namespace platz1de\EasyEdit\brush;

use platz1de\EasyEdit\pattern\functional\NaturalizePattern;
use platz1de\EasyEdit\pattern\functional\SmoothPattern;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Cylinder;
use platz1de\EasyEdit\selection\Sphere;
use platz1de\EasyEdit\task\selection\SetTask;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;


class BrushHandler
{
	public const BRUSH_SPHERE = 0;
	public const BRUSH_SMOOTH = 1;
	public const BRUSH_NATURALIZE = 2;
	public const BRUSH_CYLINDER = 3;

	/**
	 * @param CompoundTag $brush
	 * @param Player      $player
	 */
	public static function handleBrush(CompoundTag $brush, Player $player): void
	{
		$target = $player->getTargetBlock(100);
		if ($target !== null) {
			switch (self::nameToIdentifier($brush->getString("brushType", "", true))) {
				case self::BRUSH_SPHERE:
					SetTask::queue(new Sphere($player->getName(), $player->getLevelNonNull()->getFolderName(), $target, $brush->getShort("brushSize", 0, true)), Pattern::parse($brush->getString("brushPattern", "stone", true)), $player->asPosition());
					break;
				case self::BRUSH_SMOOTH:
					SetTask::queue(new Sphere($player->getName(), $player->getLevelNonNull()->getFolderName(), $target, $brush->getShort("brushSize", 0, true)), new SmoothPattern([], []), $player->asPosition());
					break;
				case self::BRUSH_NATURALIZE:
					SetTask::queue(new Sphere($player->getName(), $player->getLevelNonNull()->getFolderName(), $target, $brush->getShort("brushSize", 0, true)), new Pattern([new NaturalizePattern([Pattern::parse($brush->getString("topBlock", "grass", true)), Pattern::parse($brush->getString("middleBlock", "dirt", true)), Pattern::parse($brush->getString("bottomBlock", "stone", true))], [])], []), $player->asPosition());
					break;
				case self::BRUSH_CYLINDER:
					SetTask::queue(new Cylinder($player->getName(), $player->getLevelNonNull()->getFolderName(), $target, $brush->getShort("brushSize", 0, true), $brush->getShort("brushHeight", 0, true)), Pattern::parse($brush->getString("brushPattern", "stone", true)), $player->asPosition());
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
				return self::BRUSH_SPHERE;
			case "smooth":
			case "smoothing":
				return self::BRUSH_SMOOTH;
			case "naturalize":
			case "nat":
			case "naturalized":
				return self::BRUSH_NATURALIZE;
			case "cylinder":
			case "cyl":
			case "cy":
				return self::BRUSH_CYLINDER;
		}
		return self::BRUSH_SPHERE;
	}
}