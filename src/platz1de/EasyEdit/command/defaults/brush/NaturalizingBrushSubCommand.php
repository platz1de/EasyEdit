<?php

namespace platz1de\EasyEdit\command\defaults\brush;

use platz1de\EasyEdit\brush\BrushHandler;
use platz1de\EasyEdit\command\flags\CommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\flags\FloatCommandFlag;
use platz1de\EasyEdit\command\flags\StringyPatternCommandFlag;
use platz1de\EasyEdit\session\Session;
use pocketmine\nbt\tag\CompoundTag;

class NaturalizingBrushSubCommand extends BrushSubCommand
{
	protected const BRUSH_TYPE = BrushHandler::BRUSH_NATURALIZE;

	public function __construct()
	{
		parent::__construct(["naturalize", "nat", "naturalized"], ["size" => false, "top" => false, "middle" => false, "bottom" => false]);
	}

	/**
	 * @param CompoundTag           $nbt
	 * @param CommandFlagCollection $flags
	 */
	protected function applyBrushNbt(CompoundTag $nbt, CommandFlagCollection $flags): void
	{
		$nbt->setFloat("brushSize", $flags->getFloatFlag("size"));
		$nbt->setString("topBlock", $flags->getStringFlag("top"));
		$nbt->setString("middleBlock", $flags->getStringFlag("middle"));
		$nbt->setString("bottomBlock", $flags->getStringFlag("bottom"));
	}

	/**
	 * @param Session $session
	 * @return CommandFlag[]
	 */
	public function getKnownFlags(Session $session): array
	{
		return [
			"size" => FloatCommandFlag::default(5.0, "size", ["radius", "rad"], "s"),
			"top" => StringyPatternCommandFlag::default("grass", "top", [], "t"),
			"middle" => StringyPatternCommandFlag::default("dirt", "middle", [], "m"),
			"bottom" => StringyPatternCommandFlag::default("stone", "bottom", [], "b")
		];
	}
}