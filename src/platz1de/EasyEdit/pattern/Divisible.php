<?php

namespace platz1de\EasyEdit\pattern;

class Divisible extends Pattern
{
	public function isValidAt(int $x, int $y, int $z): bool
	{
		if (abs($x) % $this->args[0] !== 0 && in_array("x", $this->args, true)) {
			return false;
		}
		if (abs($y) % $this->args[0] !== 0 && in_array("y", $this->args, true)) {
			return false;
		}
		if (abs($z) % $this->args[0] !== 0 && in_array("z", $this->args, true)) {
			return false;
		}
		return true;
	}

	public function check(): void
	{
		if(!is_numeric($this->args[0] ?? "")){
			throw new ParseError("Divisible needs an int as first Argument, " . ($this->args[0] ?? "") . "given");
		}
	}
}