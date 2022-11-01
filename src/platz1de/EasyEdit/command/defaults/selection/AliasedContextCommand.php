<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\flags\CommandFlag;
use platz1de\EasyEdit\command\flags\CommandFlagCollection;
use platz1de\EasyEdit\command\flags\PatternCommandFlag;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\command\SimpleFlagArgumentCommand;
use platz1de\EasyEdit\pattern\block\SolidBlock;
use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\editing\selection\pattern\SetTask;
use pocketmine\block\BlockFactory;
use pocketmine\block\VanillaBlocks;

class AliasedContextCommand extends SimpleFlagArgumentCommand
{
	private SelectionContext $context;

	/**
	 * @param string           $name
	 * @param string[]         $aliases
	 * @param SelectionContext $context
	 */
	public function __construct(SelectionContext $context, string $name, array $aliases = [])
	{
		$this->context = $context;
		parent::__construct($name, ["pattern" => false], [KnownPermissions::PERMISSION_EDIT], $aliases);
	}

	/**
	 * @param Session               $session
	 * @param CommandFlagCollection $flags
	 */
	public function process(Session $session, CommandFlagCollection $flags): void
	{
		$session->runTask(new SetTask($session->getSelection(), $flags->getPatternFlag("pattern"), $this->context));
	}

	/**
	 * @param Session $session
	 * @return CommandFlag[]
	 */
	public function getKnownFlags(Session $session): array
	{
		return [
			"pattern" => PatternCommandFlag::default(StaticBlock::from(VanillaBlocks::STONE()), "pattern", [], "p")
		];
	}
}