<?php

namespace platz1de\EasyEdit\command;

abstract class OverloadedCommand extends EasyEditCommand
{
	/**
	 * @var array<string, SubCommand>
	 */
	private array $children = [];

	/**
	 * @param string       $name
	 * @param SubCommand[] $subcommands
	 * @param string[]     $permissions
	 * @param string[]     $aliases
	 */
	public function __construct(string $name, array $subcommands, private bool $defaultToFirst, array $permissions, array $aliases = [])
	{
		parent::__construct($name, $permissions, $aliases);
		foreach ($subcommands as $cmd) {
			foreach ($cmd->getNames() as $n) {
				$this->children[$n] = $cmd;
			}
		}
	}

	public function getExecutor(array &$args): CommandExecutor
	{
		if (isset($this->children[strtolower($args[0] ?? "")])) {
			return $this->children[strtolower(array_shift($args))];
		}
		return $this->defaultToFirst ? $this->children[array_key_first($this->children)] : $this;
	}
}