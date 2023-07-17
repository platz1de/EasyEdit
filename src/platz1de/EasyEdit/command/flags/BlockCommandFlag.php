<?php

namespace platz1de\EasyEdit\command\flags;

use platz1de\EasyEdit\command\exception\PatternParseException;
use platz1de\EasyEdit\pattern\block\BlockType;
use platz1de\EasyEdit\pattern\parser\ParseError;
use platz1de\EasyEdit\pattern\parser\PatternParser;
use platz1de\EasyEdit\session\Session;
use pocketmine\data\bedrock\block\convert\UnsupportedBlockStateException;

//TODO: Turn this into masks, as soon as they are implemented

/**
 * @extends ValuedCommandFlag<BlockType>
 */
class BlockCommandFlag extends ValuedCommandFlag
{
	/**
	 * @param Session         $session
	 * @param string          $argument
	 * @return BlockCommandFlag
	 */
	public function parseArgument(Session $session, string $argument): CommandFlag
	{
		try {
			$this->setArgument(PatternParser::getBlockType($argument, true));
			return $this;
		} catch (ParseError|UnsupportedBlockStateException $exception) {
			throw new PatternParseException(new ParseError($exception->getMessage()));
		}
	}
}