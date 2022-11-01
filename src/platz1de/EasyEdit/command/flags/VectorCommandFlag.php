<?php

namespace platz1de\EasyEdit\command\flags;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\exception\InvalidUsageException;
use platz1de\EasyEdit\session\Session;
use pocketmine\math\Vector3;

/**
 * @extends ValuedCommandFlag<Vector3>
 */
class VectorCommandFlag extends ValuedCommandFlag
{
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