<?php

namespace platz1de\EasyEdit\pattern\parser\reader;

use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\pattern\parser\ParseError;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\utils\BlockParser;

class BlockReader extends PieceReader
{
	/**
	 * @param string $piece
	 * @return Pattern
	 * @throws ParseError
	 */
	public static function readPiece(string $piece): Pattern
	{
		return StaticBlock::from(BlockParser::getBlock($piece));
	}
}