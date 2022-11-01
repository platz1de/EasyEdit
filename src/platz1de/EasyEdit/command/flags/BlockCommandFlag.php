<?php

namespace platz1de\EasyEdit\command\flags;

use platz1de\EasyEdit\command\EasyEditCommand;
use platz1de\EasyEdit\command\exception\PatternParseException;
use platz1de\EasyEdit\pattern\block\BlockType;
use platz1de\EasyEdit\pattern\parser\ParseError;
use platz1de\EasyEdit\pattern\parser\PatternParser;
use platz1de\EasyEdit\session\Session;

//TODO: Turn this into masks, as soon as they are implemented

/**
 * @extends ValuedCommandFlag<BlockType>
 */
class BlockCommandFlag extends ValuedCommandFlag
{
	/**
	 * @param EasyEditCommand $command
	 * @param Session         $session
	 * @param string          $argument
	 * @return BlockCommandFlag
	 */
	public function parseArgument(EasyEditCommand $command, Session $session, string $argument): CommandFlag
	{
		try {
			$this->setArgument(PatternParser::getBlockType($argument));
			return $this;
		} catch (ParseError $exception) {
			throw new PatternParseException($exception);
		}
	}
}