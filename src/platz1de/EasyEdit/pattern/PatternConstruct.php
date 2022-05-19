<?php

namespace platz1de\EasyEdit\pattern;

use Exception;
use platz1de\EasyEdit\pattern\type\EmptyPatternData;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\world\ChunkController;
use pocketmine\utils\AssumptionFailedError;

final class PatternConstruct extends Pattern
{
	use EmptyPatternData;

	/**
	 * @param Pattern[] $pieces
	 * @return Pattern
	 */
	public static function wrap(array $pieces): Pattern
	{
		if (count($pieces) === 1) {
			if ($pieces[0]->getWeight() === 100) {
				return $pieces[0]; //no need to wrap single patterns
			}

			return new PatternWrapper($pieces);
		}

		return new self($pieces);
	}

	public function getFor(int $x, int &$y, int $z, ChunkController $iterator, Selection $current, Selection $total): int
	{
		foreach ($this->pieces as $piece) {
			try {
				if ($piece->isValidAt($x, $y, $z, $iterator, $current, $total) && ($piece->getWeight() === 100 || random_int(1, 100) <= $piece->getWeight())) {
					return $piece->getFor($x, $y, $z, $iterator, $current, $total);
				}
			} catch (Exception) {
				throw new AssumptionFailedError("Failed to generate a random integer");
			}
		}
		return -1;
	}
}