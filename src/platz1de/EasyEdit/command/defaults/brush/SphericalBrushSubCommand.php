<?php

namespace platz1de\EasyEdit\command\defaults\brush;

use platz1de\EasyEdit\command\flags\CommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\flags\FloatCommandFlag;
use platz1de\EasyEdit\command\flags\SingularCommandFlag;
use platz1de\EasyEdit\command\flags\StringyPatternCommandFlag;
use platz1de\EasyEdit\session\Session;
use pocketmine\nbt\tag\CompoundTag;

class SphericalBrushSubCommand extends BrushSubCommand
{
	public function __construct()
	{
		parent::__construct(["sphere", "sph"], ["size" => false, "pattern" => false, "gravity" => false]);
	}

	/**
	 * @param CompoundTag           $nbt
	 * @param CommandFlagCollection $flags
	 */
	protected function applyBrushNbt(CompoundTag $nbt, CommandFlagCollection $flags): void
	{
		$nbt->setFloat("brushSize", $flags->getFloatFlag("size"));
		$nbt->setString("brushPattern", $flags->hasFlag("gravity") ? "gravity(" . $flags->getStringFlag("pattern") . ")" : $flags->getStringFlag("pattern"));
	}

	/**
	 * @param Session $session
	 * @return CommandFlag[]
	 */
	public function getKnownFlags(Session $session): array
	{
		return [
			"size" => FloatCommandFlag::default(5.0, "size", ["radius", "rad"], "s"),
			"pattern" => StringyPatternCommandFlag::default("stone", "pattern", [], "p"),
			"gravity" => new SingularCommandFlag("gravity", [], "g")
		];
	}
}