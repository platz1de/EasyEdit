<?php

namespace platz1de\EasyEdit\brush;

use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\pattern\functional\NaturalizePattern;
use platz1de\EasyEdit\pattern\parser\ParseError;
use platz1de\EasyEdit\pattern\parser\PatternParser;
use platz1de\EasyEdit\selection\Cylinder;
use platz1de\EasyEdit\selection\Sphere;
use platz1de\EasyEdit\session\SessionManager;
use platz1de\EasyEdit\task\DynamicStoredPasteTask;
use platz1de\EasyEdit\task\editing\selection\pattern\SetTask;
use platz1de\EasyEdit\task\editing\selection\SmoothTask;
use pocketmine\block\BlockLegacyIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\world\Position;
use Throwable;

class BrushHandler
{
	public const BRUSH_SPHERE = 0;
	public const BRUSH_SMOOTH = 1;
	public const BRUSH_NATURALIZE = 2;
	public const BRUSH_CYLINDER = 3;
	public const BRUSH_PASTE = 4;

	/**
	 * @param CompoundTag $brush
	 * @param Player      $player
	 */
	public static function handleBrush(CompoundTag $brush, Player $player): void
	{
		try {
			$target = $player->getTargetBlock(100, [BlockLegacyIds::STILL_WATER => true, BlockLegacyIds::FLOWING_WATER => true, BlockLegacyIds::STILL_LAVA => true, BlockLegacyIds::FLOWING_LAVA => true, BlockLegacyIds::AIR => true]);
		} catch (Throwable) {
			//No idea why this is crashing for some users, probably caused by weird binaries / plugins
			EasyEdit::getInstance()->getLogger()->warning("Player " . $player->getName() . " has thrown an exception while trying to get a target block");
			return;
		}
		if ($target !== null) {
			$session = SessionManager::get($player);
			try {
				switch (self::nameToIdentifier($brush->getString("brushType", ""))) {
					case self::BRUSH_SPHERE:
						$session->runTask(new SetTask(Sphere::aroundPoint($player->getWorld()->getFolderName(), $target->getPosition(), $brush->getFloat("brushSize", 0)), PatternParser::parseInternal($brush->getString("brushPattern", "stone"))));
						break;
					case self::BRUSH_SMOOTH:
						$session->runTask(new SmoothTask(Sphere::aroundPoint($player->getWorld()->getFolderName(), $target->getPosition(), $brush->getFloat("brushSize", 0))));
						break;
					case self::BRUSH_NATURALIZE:
						$session->runTask(new SetTask(Sphere::aroundPoint($player->getWorld()->getFolderName(), $target->getPosition(), $brush->getFloat("brushSize", 0)), new NaturalizePattern(PatternParser::parseInternal($brush->getString("topBlock", "grass")), PatternParser::parseInternal($brush->getString("middleBlock", "dirt")), PatternParser::parseInternal($brush->getString("bottomBlock", "stone")))));
						break;
					case self::BRUSH_CYLINDER:
						$session->runTask(new SetTask(Cylinder::aroundPoint($player->getWorld()->getFolderName(), $target->getPosition(), $brush->getFloat("brushSize", 0), $brush->getShort("brushHeight", 0)), PatternParser::parseInternal($brush->getString("brushPattern", "stone"))));
						break;
					case self::BRUSH_PASTE:
						$clipboard = SessionManager::get($player)->getClipboard();
						if (!$clipboard->isValid()) {
							$session->sendMessage("no-clipboard");
							return;
						}
						$session->runTask(new DynamicStoredPasteTask($clipboard, Position::fromObject($target->getPosition()->up(), $target->getPosition()->getWorld()), true, $brush->getByte("isInsert", 0)));
				}
			} catch (ParseError $e) {
				$session->sendMessage("pattern-invalid", ["{message}" => $e->getMessage()]);
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
			"paste", "pasting" => self::BRUSH_PASTE
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
			self::BRUSH_PASTE => "paste"
		};
	}
}