<?php

namespace platz1de\EasyEdit\command\defaults;

use platz1de\EasyEdit\brush\BrushHandler;
use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\pattern\ParseError;
use platz1de\EasyEdit\pattern\Pattern;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\NamedTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class BrushCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/brush", "Create a new Brush", "easyedit.command.brush", "//brush sphere [radius] [pattern]\n//brush smooth [radius]\n//brush naturalize [radius] [topBlock] [middleBlock] [bottomBlock]", ["/br"]);
	}

	/**
	 * @param Player $player
	 * @param array  $args
	 * @param array  $flags
	 */
	public function process(Player $player, array $args, array $flags): void
	{
		$type = BrushHandler::nameToIdentifier($args[0] ?? "");

		$item = ItemFactory::get(ItemIds::WOODEN_SHOVEL);
		switch ($type) {
			case 0:
				$item->setNamedTagEntry(new StringTag("brushType", "sphere"));
				try {
					Pattern::parse($args[2] ?? "stone");
				} catch (ParseError $exception) {
					$player->sendMessage($exception->getMessage());
					return;
				}
				$item->setNamedTagEntry(new ShortTag("brushSize", $args[1] ?? 3));
				$item->setNamedTagEntry(new StringTag("brushPattern", $args[2] ?? "stone"));
				break;
			case 1:
				$item->setNamedTagEntry(new StringTag("brushType", "smooth"));
				$item->setNamedTagEntry(new ShortTag("brushSize", $args[1] ?? 5));
				break;
			case 2:
				$item->setNamedTagEntry(new StringTag("brushType", "naturalize"));
				try {
					Pattern::parse($args[2] ?? "grass");
					Pattern::parse($args[3] ?? "dirt");
					Pattern::parse($args[4] ?? "stone");
				} catch (ParseError $exception) {
					$player->sendMessage($exception->getMessage());
					return;
				}
				$item->setNamedTagEntry(new ShortTag("brushSize", $args[1] ?? 4));
				$item->setNamedTagEntry(new StringTag("topBlock", $args[2] ?? "grass"));
				$item->setNamedTagEntry(new StringTag("middleBlock", $args[3] ?? "dirt"));
				$item->setNamedTagEntry(new StringTag("bottomBlock", $args[4] ?? "stone"));
				break;
		}
		$item->setLore(array_map(static function (NamedTag $tag) {
			return $tag->getName() . ": " . $tag->getValue();
		}, $item->getNamedTag()->getValue()));
		$item->setCustomName(TextFormat::GOLD . "Brush");
		$player->getInventory()->setItem($player->getInventory()->getHeldItemIndex(), $item);
	}
}