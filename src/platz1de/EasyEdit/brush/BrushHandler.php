<?php

namespace platz1de\EasyEdit\brush;

use platz1de\EasyEdit\pattern\functional\NaturalizePattern;
use platz1de\EasyEdit\pattern\functional\SmoothPattern;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\pattern\PatternParser;
use platz1de\EasyEdit\selection\Cylinder;
use platz1de\EasyEdit\selection\Sphere;
use platz1de\EasyEdit\task\selection\SetTask;
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
					SetTask::queue(new Sphere($player->getName(), $player->getWorld()->getFolderName(), $target->getPos(), $brush->getShort("brushSize", 0)), PatternParser::parse($brush->getString("brushPattern", "stone")), $player->getPosition());
					break;
				case self::BRUSH_SMOOTH:
					SetTask::queue(new Sphere($player->getName(), $player->getWorld()->getFolderName(), $target->getPos(), $brush->getShort("brushSize", 0)), new SmoothPattern([]), $player->getPosition());
					break;
				case self::BRUSH_NATURALIZE:
					SetTask::queue(new Sphere($player->getName(), $player->getWorld()->getFolderName(), $target->getPos(), $brush->getShort("brushSize", 0)), new Pattern([new NaturalizePattern([PatternParser::parse($brush->getString("topBlock", "grass")), PatternParser::parse($brush->getString("middleBlock", "dirt")), PatternParser::parse($brush->getString("bottomBlock", "stone"))])]), $player->getPosition());
					break;
				case self::BRUSH_CYLINDER:
					SetTask::queue(new Cylinder($player->getName(), $player->getWorld()->getFolderName(), $target->getPos(), $brush->getShort("brushSize", 0), $brush->getShort("brushHeight", 0)), PatternParser::parse($brush->getString("brushPattern", "stone")), $player->getPosition());
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
			default:
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
	}

	/**
	 * @param int $brush
	 * @return string
	 */
	public static function identifierToName(int $brush): string
	{
		switch ($brush) {
			default:
			case self::BRUSH_SPHERE:
				return "sphere";
			case self::BRUSH_SMOOTH;
				return "smooth";
			case self::BRUSH_NATURALIZE;
				return "naturalize";
			case self::BRUSH_CYLINDER;
				return "cylinder";
		}
	}
}