<?php

namespace platz1de\EasyEdit\command\defaults\utility;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\session\Session;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\TextFormat;

class BuilderRodCommand extends EasyEditCommand
{
	public function __construct()
	{
		parent::__construct("/builderrod", [KnownPermissions::PERMISSION_ROD], ["/rod", "/builderwand"]);
	}

	/**
	 * @param Session  $session
	 * @param string[] $args
	 */
	public function process(Session $session, array $args): void
	{
		$item = VanillaItems::BLAZE_ROD()->setNamedTag(CompoundTag::create()->setByte("isBuilderRod", 1))->setCustomName(TextFormat::GOLD . "BuilderRod");
		$session->asPlayer()->getInventory()->setItem($session->asPlayer()->getInventory()->getHeldItemIndex(), $item);
	}
}