<?php

namespace platz1de\EasyEdit\command\defaults;

use platz1de\EasyEdit\brush\BrushHandler;
use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\pattern\ParseError;
use platz1de\EasyEdit\pattern\PatternParser;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\types\command\CommandEnum;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class BrushCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/brush", "Create a new Brush", "easyedit.command.brush", ["/br"]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		$type = BrushHandler::nameToIdentifier($args[0] ?? "");

		$nbt = CompoundTag::create()->setString("brushType", BrushHandler::identifierToName($type));
		switch ($type) {
			case BrushHandler::BRUSH_SPHERE:
				try {
					PatternParser::parseInput($args[2] ?? "stone", $player);
				} catch (ParseError $exception) {
					$player->sendMessage($exception->getMessage());
					return;
				}
				$nbt->setFloat("brushSize", (float) ($args[1] ?? 3));
				$nbt->setString("brushPattern", $args[2] ?? "stone");
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
					$player->sendMessage($exception->getMessage());
					return;
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
					$player->sendMessage($exception->getMessage());
					return;
				}
				$nbt->setFloat("brushSize", (float) ($args[1] ?? 4));
				$nbt->setShort("brushHeight", (int) ($args[2] ?? 2));
				$nbt->setString("brushPattern", $args[3] ?? "stone");
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

	/**
	 * @return CommandParameter[][]
	 */
	public function getCommandOverloads(): array
	{
		return [
			[
				CommandParameter::enum("type", new CommandEnum("type", ["sphere"]), 0),
				CommandParameter::standard("radius", AvailableCommandsPacket::ARG_TYPE_FLOAT, 0, true),
				CommandParameter::standard("pattern", AvailableCommandsPacket::ARG_TYPE_RAWTEXT, 0, true)
			],
			[
				CommandParameter::enum("type", new CommandEnum("type", ["smooth"]), 0),
				CommandParameter::standard("radius", AvailableCommandsPacket::ARG_TYPE_FLOAT, 0, true)
			],
			[
				CommandParameter::enum("type", new CommandEnum("type", ["naturalize"]), 0),
				CommandParameter::standard("radius", AvailableCommandsPacket::ARG_TYPE_FLOAT, 0, true),
				CommandParameter::standard("topBlock", AvailableCommandsPacket::ARG_TYPE_RAWTEXT, 0, true),
				CommandParameter::standard("middleBlock", AvailableCommandsPacket::ARG_TYPE_RAWTEXT, 0, true),
				CommandParameter::standard("bottomBlock", AvailableCommandsPacket::ARG_TYPE_RAWTEXT, 0, true)
			],
			[
				CommandParameter::enum("type", new CommandEnum("type", ["cylinder"]), 0),
				CommandParameter::standard("radius", AvailableCommandsPacket::ARG_TYPE_FLOAT, 0, true),
				CommandParameter::standard("height", AvailableCommandsPacket::ARG_TYPE_INT, 0, true),
				CommandParameter::standard("pattern", AvailableCommandsPacket::ARG_TYPE_RAWTEXT, 0, true)
			]
		];
	}
}