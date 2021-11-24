<?php

namespace platz1de\EasyEdit\brush;

use platz1de\EasyEdit\pattern\functional\NaturalizePattern;
use platz1de\EasyEdit\pattern\functional\SmoothPattern;
use platz1de\EasyEdit\pattern\parser\PatternParser;
use platz1de\EasyEdit\selection\Cylinder;
use platz1de\EasyEdit\selection\Sphere;
use platz1de\EasyEdit\task\editing\selection\pattern\SetTask;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;


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
			switch (self::nameToIdentifier($brush->getString("brushType", ""))) {
				case self::BRUSH_SPHERE:
					SetTask::queue(Sphere::aroundPoint($player->getName(), $player->getWorld()->getFolderName(), $target->getPosition(), $brush->getFloat("brushSize", 0)), PatternParser::parseInternal($brush->getString("brushPattern", "stone")), $player->getPosition());
					break;
				case self::BRUSH_SMOOTH:
					SetTask::queue(Sphere::aroundPoint($player->getName(), $player->getWorld()->getFolderName(), $target->getPosition(), $brush->getFloat("brushSize", 0)), SmoothPattern::from([]), $player->getPosition());
					break;
				case self::BRUSH_NATURALIZE:
					SetTask::queue(Sphere::aroundPoint($player->getName(), $player->getWorld()->getFolderName(), $target->getPosition(), $brush->getFloat("brushSize", 0)), NaturalizePattern::from([PatternParser::parseInternal($brush->getString("topBlock", "grass")), PatternParser::parseInternal($brush->getString("middleBlock", "dirt")), PatternParser::parseInternal($brush->getString("bottomBlock", "stone"))]), $player->getPosition());
					break;
				case self::BRUSH_CYLINDER:
					SetTask::queue(Cylinder::aroundPoint($player->getName(), $player->getWorld()->getFolderName(), $target->getPosition(), $brush->getFloat("brushSize", 0), $brush->getShort("brushHeight", 0)), PatternParser::parseInternal($brush->getString("brushPattern", "stone")), $player->getPosition());
			}
		}
	}

	/**
	 * @param string $brush
	 * @return int
	 */
	public static function nameToIdentifier(string $brush): int
	{
		return match (strtolower($brush)) {
			default => self::BRUSH_SPHERE,
			"smooth", "smoothing" => self::BRUSH_SMOOTH,
			"naturalize", "nat", "naturalized" => self::BRUSH_NATURALIZE,
			"cylinder", "cyl", "cy" => self::BRUSH_CYLINDER,
		};
	}

	/**
	 * @param int $brush
	 * @return string
	 */
	public static function identifierToName(int $brush): string
	{
		return match ($brush) {
			default => "sphere",
			self::BRUSH_SMOOTH => "smooth",
			self::BRUSH_NATURALIZE => "naturalize",
			self::BRUSH_CYLINDER => "cylinder",
		};
	}
}