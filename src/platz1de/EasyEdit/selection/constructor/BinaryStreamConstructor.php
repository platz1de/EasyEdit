<?php

namespace platz1de\EasyEdit\selection\constructor;

use BadMethodCallException;
use Closure;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class BinaryStreamConstructor extends ShapeConstructor
{
	private ExtendedBinaryStream $stream;

	public function __construct(Closure $closure, ExtendedBinaryStream $stream)
	{
		parent::__construct($closure);
		$this->stream = $stream;
	}

	public function getBlockCount(): int
	{
		return (int) (strlen($this->stream->getBuffer()) / 16); //each blocks consists of 4 integers, which are 4 bytes each
	}

	public function moveTo(int $chunk): void
	{
		$this->stream->rewind();
		World::getXZ($chunk, $x, $z);
		$minX = $x << 4;
		$minZ = $z << 4;
		$maxX = $minX + 15;
		$maxZ = $minZ + 15;
		$closure = $this->closure;
		while (!$this->stream->feof()) {
			$o = $this->stream->getOffset();
			$x = $this->stream->getInt();
			$y = $this->stream->getInt();
			$z = $this->stream->getInt();
			if ($x < $minX || $x > $maxX || $z < $minZ || $z > $maxZ) {
				$this->stream->setOffset($o);
				$this->stream = new ExtendedBinaryStream($this->stream->getRemaining());
				break;
			}
			$closure($x, $y, $z, $this->stream->getInt());
		}
	}

	public function offset(Vector3 $offset): ShapeConstructor
	{
		throw new BadMethodCallException("Binary streams can't be offset");
	}
}