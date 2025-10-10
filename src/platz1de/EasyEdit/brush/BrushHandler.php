<?php

namespace platz1de\EasyEdit\brush;

use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\math\OffGridBlockVector;
use platz1de\EasyEdit\pattern\functional\NaturalizePattern;
use platz1de\EasyEdit\pattern\parser\ParseError;
use platz1de\EasyEdit\pattern\parser\PatternParser;
use platz1de\EasyEdit\selection\Cone;
use platz1de\EasyEdit\selection\Cube;
use platz1de\EasyEdit\selection\Cylinder;
use platz1de\EasyEdit\selection\Ellipsoid;
use platz1de\EasyEdit\selection\Extrude;
use platz1de\EasyEdit\selection\Flatten;
use platz1de\EasyEdit\selection\Pyramid;
use platz1de\EasyEdit\selection\Sphere;
use platz1de\EasyEdit\selection\Torus;
use platz1de\EasyEdit\session\SessionManager;
use platz1de\EasyEdit\task\editing\DynamicPasteTask;
use platz1de\EasyEdit\task\editing\SetTask;
use platz1de\EasyEdit\task\editing\SmoothTask;
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
	public const BRUSH_CUBE = 5;
	public const BRUSH_PYRAMID = 6;
	public const BRUSH_CONE = 7;
	public const BRUSH_TORUS = 8;
	public const BRUSH_ELLIPSOID = 9;

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
					case self::BRUSH_CUBE:
						$size = $brush->getInt("brushSize", 5);
						$session->runSettingTask(new SetTask(new Cube($world, $point->add(-$size, -$size, -$size)->forceIntoGrid(), $point->add($size, $size, $size)->forceIntoGrid()), PatternParser::parseInternal($brush->getString("brushPattern", "stone"))));
						break;
					case self::BRUSH_PYRAMID:
						$session->runSettingTask(new SetTask(new Pyramid($world, $point, $brush->getInt("brushSize", 5), $brush->getInt("brushHeight", 10)), PatternParser::parseInternal($brush->getString("brushPattern", "stone"))));
						break;
					case self::BRUSH_CONE:
						$session->runSettingTask(new SetTask(new Cone($world, $point, $brush->getFloat("brushSize", 5.0), $brush->getInt("brushHeight", 10)), PatternParser::parseInternal($brush->getString("brushPattern", "stone"))));
						break;
					case self::BRUSH_TORUS:
						$session->runSettingTask(new SetTask(new Torus($world, $point, $brush->getFloat("majorRadius", 8.0), $brush->getFloat("minorRadius", 3.0)), PatternParser::parseInternal($brush->getString("brushPattern", "stone"))));
						break;
					case self::BRUSH_ELLIPSOID:
						$session->runSettingTask(new SetTask(new Ellipsoid($world, $point, $brush->getFloat("radiusX", 5.0), $brush->getFloat("radiusY", 5.0), $brush->getFloat("radiusZ", 5.0)), PatternParser::parseInternal($brush->getString("brushPattern", "stone"))));
						break;
					case self::BRUSH_PASTE:
						$clipboard = SessionManager::get($player)->getClipboard();
						if (!$clipboard->isValid()) {
							$session->sendMessage("no-clipboard");
							return;
						}
						$session->runSettingTask(new DynamicPasteTask($world, $clipboard, $point->up(), $brush->getByte("isInsert", 0)));
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
			"paste", "pasting" => self::BRUSH_PASTE,
			"cube", "cb" => self::BRUSH_CUBE,
			"pyramid", "pyr" => self::BRUSH_PYRAMID,
			"cone", "co" => self::BRUSH_CONE,
			"torus", "ring" => self::BRUSH_TORUS,
			"ellipsoid", "ell" => self::BRUSH_ELLIPSOID
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
			self::BRUSH_PASTE => "paste",
			self::BRUSH_CUBE => "cube",
			self::BRUSH_PYRAMID => "pyramid",
			self::BRUSH_CONE => "cone",
			self::BRUSH_TORUS => "torus",
			self::BRUSH_ELLIPSOID => "ellipsoid"
		};
	}
}
