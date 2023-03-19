<?php

namespace platz1de\EasyEdit\selection\constructor;

use BadMethodCallException;
use Closure;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\math\Vector3;
use pocketmine\utils\Utils;
use pocketmine\world\World;

class BinaryStreamConstructor extends ShapeConstructor
{
	/**
	 * @param Closure              $closure
	 * @param ExtendedBinaryStream $stream
	 * @param array<int, int>      $chunks
	 */
	public function __construct(Closure $closure, private ExtendedBinaryStream $stream, private array $chunks)
	{
		Utils::validateCallableSignature(static function (int $x, int $y, int $z, int $block): void {}, $closure);
		$this->closure = $closure;
	}

	public function getBlockCount(): int
	{
		return (int) (strlen($this->stream->getBuffer()) / 16); //each blocks consists of 4 integers, which are 4 bytes each
	}

	public function moveTo(int $chunk): void
	{
		$this->stream->setOffset($this->chunks[$chunk]);
		World::getXZ($chunk, $x, $z);
		$minX = $x << 4;
		$minZ = $z << 4;
		$maxX = $minX + 15;
		$maxZ = $minZ + 15;
		$closure = $this->closure;
		while (!$this->stream->feof()) {
			$x = $this->stream->getInt();
			$y = $this->stream->getInt();
			$z = $this->stream->getInt();
			if ($x < $minX || $x > $maxX || $z < $minZ || $z > $maxZ) {
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