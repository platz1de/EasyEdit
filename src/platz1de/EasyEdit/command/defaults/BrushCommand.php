<?php

namespace platz1de\EasyEdit\command\defaults;

use platz1de\EasyEdit\brush\BrushHandler;
use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\pattern\ParseError;
use platz1de\EasyEdit\pattern\Pattern;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\NamedTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class BrushCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/brush", "Create a new Brush", "easyedit.command.brush", "//brush sphere [radius] [pattern]\n//brush smooth [radius]\n//brush naturalize [radius] [topBlock] [middleBlock] [bottomBlock]\n//brush cylinder [radius] [height] [pattern]", ["/br"]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		$type = BrushHandler::nameToIdentifier($args[0] ?? "");

		$item = VanillaItems::WOODEN_SHOVEL();
		switch ($type) {
			case BrushHandler::BRUSH_SPHERE:
				$item->setNamedTagEntry(new StringTag("brushType", "sphere"));
				try {
					Pattern::parse($args[2] ?? "stone");
				} catch (ParseError $exception) {
					$player->sendMessage($exception->getMessage());
					return;
				}
				$item->setNamedTagEntry(new ShortTag("brushSize", (int) ($args[1] ?? 3)));
				$item->setNamedTagEntry(new StringTag("brushPattern", $args[2] ?? "stone"));
				break;
			case BrushHandler::BRUSH_SMOOTH:
				$item->setNamedTagEntry(new StringTag("brushType", "smooth"));
				$item->setNamedTagEntry(new ShortTag("brushSize", (int) ($args[1] ?? 5)));
				break;
			case BrushHandler::BRUSH_NATURALIZE:
				$item->setNamedTagEntry(new StringTag("brushType", "naturalize"));
				try {
					Pattern::parse($args[2] ?? "grass");
					Pattern::parse($args[3] ?? "dirt");
					Pattern::parse($args[4] ?? "stone");
				} catch (ParseError $exception) {
					$player->sendMessage($exception->getMessage());
					return;
				}
				$item->setNamedTagEntry(new ShortTag("brushSize", (int) ($args[1] ?? 4)));
				$item->setNamedTagEntry(new StringTag("topBlock", $args[2] ?? "grass"));
				$item->setNamedTagEntry(new StringTag("middleBlock", $args[3] ?? "dirt"));
				$item->setNamedTagEntry(new StringTag("bottomBlock", $args[4] ?? "stone"));
				break;
			case BrushHandler::BRUSH_CYLINDER:
				$item->setNamedTagEntry(new StringTag("brushType", "cylinder"));
				try {
					Pattern::parse($args[3] ?? "stone");
				} catch (ParseError $exception) {
					$player->sendMessage($exception->getMessage());
					return;
				}
				$item->setNamedTagEntry(new ShortTag("brushSize", (int) ($args[1] ?? 4)));
				$item->setNamedTagEntry(new ShortTag("brushHeight", (int) ($args[2] ?? 2)));
				$item->setNamedTagEntry(new StringTag("brushPattern", $args[3] ?? "stone"));
		}
		$item->setLore(array_map(static function (NamedTag $tag): string {
			return $tag->getName() . ": " . $tag->getValue();
		}, $item->getNamedTag()->getValue()));
		$item->setCustomName(TextFormat::GOLD . "Brush");
		$player->getInventory()->setItem($player->getInventory()->getHeldItemIndex(), $item);
	}
}