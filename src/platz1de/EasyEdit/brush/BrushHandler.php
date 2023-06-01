<?php

namespace platz1de\EasyEdit\brush;

use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\math\OffGridBlockVector;
use platz1de\EasyEdit\pattern\functional\NaturalizePattern;
use platz1de\EasyEdit\pattern\parser\ParseError;
use platz1de\EasyEdit\pattern\parser\PatternParser;
use platz1de\EasyEdit\selection\Cylinder;
use platz1de\EasyEdit\selection\Sphere;
use platz1de\EasyEdit\session\SessionManager;
use platz1de\EasyEdit\task\DynamicStoredPasteTask;
use platz1de\EasyEdit\task\editing\SetTask;
use platz1de\EasyEdit\task\editing\smooth\SmoothTask;
use pocketmine\block\BlockTypeIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
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
			$target = $player->getTargetBlock(100, [BlockTypeIds::WATER => true, BlockTypeIds::LAVA => true, BlockTypeIds::AIR => true]);
		} catch (Throwable) {
			//No idea why this is crashing for some users, probably caused by weird binaries / plugins
			EasyEdit::getInstance()->getLogger()->warning("Player " . $player->getName() . " has thrown an exception while trying to get a target block");
			return;
		}
		if ($target !== null) {
			$session = SessionManager::get($player);
			$point = OffGridBlockVector::fromVector($target->getPosition());
			$world = $player->getWorld()->getFolderName();
			try {
				switch (self::nameToIdentifier($brush->getString("brushType", ""))) {
					case self::BRUSH_SPHERE:
						$session->runSettingTask(new SetTask(new Sphere($world, $point, $brush->getFloat("brushSize", 0)), PatternParser::parseInternal($brush->getString("brushPattern", "stone"))));
						break;
					case self::BRUSH_SMOOTH:
						$session->runSettingTask(new SmoothTask(new Sphere($world, $point, $brush->getFloat("brushSize", 0))));
						break;
					case self::BRUSH_NATURALIZE:
						$session->runSettingTask(new SetTask(new Sphere($world, $point, $brush->getFloat("brushSize", 0)), new NaturalizePattern(PatternParser::parseInternal($brush->getString("topBlock", "grass")), PatternParser::parseInternal($brush->getString("middleBlock", "dirt")), PatternParser::parseInternal($brush->getString("bottomBlock", "stone")))));
						break;
					case self::BRUSH_CYLINDER:
						$session->runSettingTask(new SetTask(new Cylinder($world, $point, $brush->getFloat("brushSize", 0), $brush->getShort("brushHeight", 0)), PatternParser::parseInternal($brush->getString("brushPattern", "stone"))));
						break;
					case self::BRUSH_PASTE:
						$clipboard = SessionManager::get($player)->getClipboard();
						if (!$clipboard->isValid()) {
							$session->sendMessage("no-clipboard");
							return;
						}
						$session->runSettingTask(new DynamicStoredPasteTask($clipboard, $world, $point->up(), $brush->getByte("isInsert", 0)));
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