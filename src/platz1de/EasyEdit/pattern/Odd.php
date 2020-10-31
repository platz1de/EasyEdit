<?php

namespace platz1de\EasyEdit\pattern;

class Odd extends Pattern
{
	public function isValidAt(int $x, int $y, int $z): bool
	{
		if (abs($x) % 2 !== 1 && in_array("x", $this->args, true)) {
			return false;
		}
		if (abs($y) % 2 !== 1 && in_array("y", $this->args, true)) {
			return false;
		}
		if (abs($z) % 2 !== 1 && in_array("z", $this->args, true)) {
			return false;
		}
		return true;
	}
}