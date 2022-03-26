<?php

namespace platz1de\EasyEdit\command\defaults\utility;

use platz1de\EasyEdit\brush\BrushHandler;
use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\exception\PatternParseException;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\pattern\parser\ParseError;
use platz1de\EasyEdit\pattern\parser\PatternParser;
use platz1de\EasyEdit\utils\ArgumentParser;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class BrushCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/brush", [KnownPermissions::PERMISSION_BRUSH], ["/br"]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		ArgumentParser::requireArgumentCount($args, 1, $this);
		$type = BrushHandler::nameToIdentifier($args[0]);

		$nbt = CompoundTag::create()->setString("brushType", BrushHandler::identifierToName($type));
		switch ($type) {
			case BrushHandler::BRUSH_SPHERE:
				try {
					PatternParser::parseInput($args[2] ?? "stone", $player);
				} catch (ParseError $exception) {
					throw new PatternParseException($exception);
				}
				$nbt->setFloat("brushSize", (float) ($args[1] ?? 3));
				$nbt->setString("brushPattern", ArgumentParser::parseBool(false, $args[3] ?? null) ? "gravity(" . ($args[2] ?? "stone") . ")" : $args[2] ?? "stone");
				break;
			case BrushHandler::BRUSH_SMOOTH:
				$nbt->setFloat("brushSize", (float) ($args[1] ?? 5));
				break;
			case BrushHandler::BRUSH_NATURALIZE:
				try {
					PatternParser::parseInput($args[2] ?? "grass", $player);
					PatternParser::parseInput($args[3] ?? "dirt", $player);
					PatternParser::parseInput($args[4] ?? "stone", $player);
				} catch (ParseError $exception) {
					throw new PatternParseException($exception);
				}
				$nbt->setFloat("brushSize", (float) ($args[1] ?? 4));
				$nbt->setString("topBlock", $args[2] ?? "grass");
				$nbt->setString("middleBlock", $args[3] ?? "dirt");
				$nbt->setString("bottomBlock", $args[4] ?? "stone");
				break;
			case BrushHandler::BRUSH_CYLINDER:
				try {
					PatternParser::parseInput($args[3] ?? "stone", $player);
				} catch (ParseError $exception) {
					throw new PatternParseException($exception);
				}
				$nbt->setFloat("brushSize", (float) ($args[1] ?? 4));
				$nbt->setShort("brushHeight", (int) ($args[2] ?? 2));
				$nbt->setString("brushPattern", ArgumentParser::parseBool(false, $args[4] ?? null) ? "gravity(" . ($args[3] ?? "stone") . ")" : $args[3] ?? "stone");
				break;
			case BrushHandler::BRUSH_PASTE:
				$nbt->setByte("isInsert", ArgumentParser::parseBool(false, $args[1] ?? null) ? 1 : 0);
		}
		$item = VanillaItems::WOODEN_SHOVEL()->setNamedTag($nbt);
		$lore = [];
		foreach ($nbt->getValue() as $name => $value) {
			$lore[] = $name . ": " . $value;
		}
		$item->setLore($lore);
		$item->setCustomName(TextFormat::GOLD . "Brush");
		$player->getInventory()->setItem($player->getInventory()->getHeldItemIndex(), $item);
	}
}