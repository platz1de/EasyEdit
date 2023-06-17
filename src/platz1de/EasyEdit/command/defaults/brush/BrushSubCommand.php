<?php

namespace platz1de\EasyEdit\command\defaults\brush;

use platz1de\EasyEdit\brush\BrushHandler;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\command\SubCommand;
use platz1de\EasyEdit\session\Session;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\TextFormat;

abstract class BrushSubCommand extends SubCommand
{
	protected const BRUSH_TYPE = BrushHandler::BRUSH_SPHERE;

	/**
	 * @param string[]            $names
	 * @param array<string, bool> $flagOrder
	 */
	public function __construct(array $names, array $flagOrder)
	{
		parent::__construct($names, $flagOrder, [KnownPermissions::PERMISSION_BRUSH]);
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		$nbt = CompoundTag::create()->setString("brushType", BrushHandler::identifierToName(static::BRUSH_TYPE));
		$this->applyBrushNbt($nbt, $flags);
		$item = VanillaItems::WOODEN_SHOVEL()->setNamedTag($nbt);
		$lore = [];
		foreach ($nbt->getValue() as $name => $value) {
			$lore[] = $name . ": " . $value;
		}
		$item->setLore($lore);
		$item->setCustomName(TextFormat::GOLD . "Brush");
		$session->asPlayer()->getInventory()->setItem($session->asPlayer()->getInventory()->getHeldItemIndex(), $item);
	}

	/**
	 * @param CompoundTag           $nbt
	 * @param CommandFlagCollection $flags
	 */
	abstract protected function applyBrushNbt(CompoundTag $nbt, CommandFlagCollection $flags): void;
}