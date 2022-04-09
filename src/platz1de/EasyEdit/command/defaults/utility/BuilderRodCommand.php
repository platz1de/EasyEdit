<?php

namespace platz1de\EasyEdit\command\defaults\utility;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class BuilderRodCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/builderrod", [KnownPermissions::PERMISSION_ROD], ["/rod", "/builderwand"]);
	}

	/**
	 * @param Player   $player
	 * @param string[] $args
	 */
	public function process(Player $player, array $args): void
	{
		$item = VanillaItems::BLAZE_ROD()->setNamedTag(CompoundTag::create()->setByte("isBuilderRod", 1))->setCustomName(TextFormat::GOLD . "BuilderRod");
		$player->getInventory()->setItem($player->getInventory()->getHeldItemIndex(), $item);
	}
}