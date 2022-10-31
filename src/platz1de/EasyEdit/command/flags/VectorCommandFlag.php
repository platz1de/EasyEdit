<?php

namespace platz1de\EasyEdit\command\flags;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\exception\InvalidUsageException;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\session\Session;
use pocketmine\math\Vector3;

class VectorCommandFlag extends CommandFlag
{
	private Vector3 $argument;

	/**
	 * @param Vector3     $argument
	 * @param string      $name
	 * @param string[]    $aliases
	 * @param string|null $id
	 * @return VectorCommandFlag
	 */
	public static function with(Vector3 $argument, string $name, array $aliases = null, string $id = null): self
	{
		$flag = new self($name, $aliases, $id);
		$flag->hasArgument = true;
		$flag->argument = $argument;
		return $flag;
	}

	public function setArgument(Vector3 $argument): Vector3
	{
		return $this->argument = $argument;
	}

	public function getArgument() : Vector3
	{
		return $this->argument;
	}

	/**
	 * @param EasyEditCommand $command
	 * @param Session         $session
	 * @param string          $argument
	 * @return VectorCommandFlag
	 */
	public function parseArgument(EasyEditCommand $command, Session $session, string $argument): self
	{
		$vector = explode(",", $argument);
		if (count($vector) !== 3) {
			throw new InvalidUsageException($command);
		}
		$this->setArgument(new Vector3((int) $vector[0], (int) $vector[1], (int) $vector[2]));
		return $this;
	}
}