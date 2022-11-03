<?php

namespace platz1de\EasyEdit\selection\constructor;

use Closure;
use pocketmine\math\Vector3;
use pocketmine\utils\Utils;

abstract class ShapeConstructor
{
	protected Closure $closure;

	public function __construct(Closure $closure)
	{
		Utils::validateCallableSignature(static function (int $x, int $y, int $z): void { }, $closure);
		$this->closure = $closure;
	}

	abstract public function getBlockCount(): int;

	abstract public function moveTo(int $chunk): void;

	abstract public function offset(Vector3 $offset): self;
}