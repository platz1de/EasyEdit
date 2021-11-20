<?php

namespace platz1de\EasyEdit\pattern\parser\reader;

use platz1de\EasyEdit\pattern\parser\ParseError;
use platz1de\EasyEdit\pattern\Pattern;

abstract class PieceReader
{
	/**
	 * @param string $piece
	 * @return Pattern
	 * @throws ParseError
	 */
	abstract public static function readPiece(string $piece): Pattern;
}