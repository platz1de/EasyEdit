<?php

namespace platz1de\EasyEdit\command\defaults\brush;

use platz1de\EasyEdit\brush\BrushHandler;
use platz1de\EasyEdit\command\flags\CommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\flags\SingularCommandFlag;
use platz1de\EasyEdit\session\Session;
use pocketmine\nbt\tag\CompoundTag;

class PastingBrushSubCommand extends BrushSubCommand
{
	protected const BRUSH_TYPE = BrushHandler::BRUSH_PASTE;

	public function __construct()
	{
		parent::__construct(["paste", "pasting"], ["insert" => false]);
	}

	/**
	 * @param CompoundTag           $nbt
	 * @param CommandFlagCollection $flags
	 */
	protected function applyBrushNbt(CompoundTag $nbt, CommandFlagCollection $flags): void
	{
		$nbt->setByte("isInsert", $flags->hasFlag("insert") ? 1 : 0);
	}

	/**
	 * @param Session $session
	 * @return CommandFlag[]
	 */
	public function getKnownFlags(Session $session): array
	{
		return [
			"insert" => new SingularCommandFlag("insert", [], "i")
		];
	}
}