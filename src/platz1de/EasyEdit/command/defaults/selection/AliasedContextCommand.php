<?php

namespace platz1de\EasyEdit\command\defaults\selection;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\KnownPermissions;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\session\Session;
use platz1de\EasyEdit\task\editing\selection\pattern\SetTask;
use platz1de\EasyEdit\utils\ArgumentParser;

abstract class AliasedContextCommand extends EasyEditCommand
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
		parent::__construct($name, [KnownPermissions::PERMISSION_EDIT], $aliases);
	}

	/**
	 * @param Session  $session
	 * @param string[] $args
	 */
	public function process(Session $session, array $args): void
	{
		$session->runTask(new SetTask($session->getSelection(), ArgumentParser::parseCombinedPattern($session, $args, 0, "stone"), $this->context));
	}
}