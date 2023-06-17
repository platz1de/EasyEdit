<?php

namespace platz1de\EasyEdit\command\defaults\brush;

use platz1de\EasyEdit\brush\BrushHandler;
use platz1de\EasyEdit\command\flags\CommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\flags\FloatCommandFlag;
use platz1de\EasyEdit\session\Session;
use pocketmine\nbt\tag\CompoundTag;

class SmoothingBrushSubCommand extends BrushSubCommand
{
	protected const BRUSH_TYPE = BrushHandler::BRUSH_SMOOTH;

	public function __construct()
	{
		parent::__construct(["smooth", "smoothing", "smth", "sm"], ["size" => false]);
	}

	/**
	 * @param CompoundTag           $nbt
	 * @param CommandFlagCollection $flags
	 */
	protected function applyBrushNbt(CompoundTag $nbt, CommandFlagCollection $flags): void
	{
		$nbt->setFloat("brushSize", $flags->getFloatFlag("size"));
	}

	/**
	 * @param Session $session
	 * @return CommandFlag[]
	 */
	public function getKnownFlags(Session $session): array
	{
		return [
			"size" => FloatCommandFlag::default(5.0, "size", ["radius", "rad"], "s")
		];
	}
}