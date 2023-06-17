<?php

namespace platz1de\EasyEdit\command;

abstract class SubCommand implements CommandExecutor
{
	use FlagArgumentParser;

	/**
	 * @param string[]            $names
	 * @param array<string, bool> $flagOrder
	 * @param string[]            $permissions
	 */
	public function __construct(private array $names, array $flagOrder, private array $permissions)
	{
		$this->flagOrder = $flagOrder;
	}

	/**
	 * @return string[]
	 */
	public function getNames(): array
	{
		return $this->names;
	}

	/**
	 * @return string[]
	 */
	public function getPermissions(): array
	{
		return $this->permissions;
	}
}