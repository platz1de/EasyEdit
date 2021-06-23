<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\LoaderManager;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\utils\Utils;
use UnexpectedValueException;

class DynamicBlockListSelection extends BlockListSelection
{
	/**
	 * @var Vector3
	 */
	private $point;

	/**
	 * DynamicBlockListSelection constructor.
	 * @param string       $player
	 * @param Vector3|null $place
	 * @param Vector3|null $pos1
	 * @param Vector3|null $pos2
	 * @param bool         $piece
	 */
	public function __construct(string $player, ?Vector3 $place = null, ?Vector3 $pos1 = null, ?Vector3 $pos2 = null, bool $piece = false)
	{
		if ($pos1 instanceof Vector3 && $pos2 instanceof Vector3) {
			$pos2 = $pos2->subtract($pos1);
		}
		parent::__construct($player, "", new Vector3(), $pos2 ?? null, $piece);
		if ($pos1 instanceof Vector3 && $place instanceof Vector3) {
			$this->point = $place->subtract($pos1);
		}
	}

	/**
	 * @param Position $place
	 * @return Chunk[]
	 */
	public function getNeededChunks(Position $place): array
	{
		$start = $this->getCubicStart()->add($place)->subtract($this->getPoint());
		$end = $this->getCubicEnd()->add($place)->subtract($this->getPoint());

		$chunks = [];
		for ($x = $start->getX() >> 4; $x <= $end->getX() >> 4; $x++) {
			for ($z = $start->getZ() >> 4; $z <= $end->getZ() >> 4; $z++) {
				$chunks[] = LoaderManager::getChunk($place->getLevelNonNull(), $x, $z);
			}
		}
		return $chunks;
	}

	/**
	 * @param int     $x
	 * @param int     $z
	 * @param Vector3 $place
	 * @return bool
	 */
	public function isChunkOfSelection(int $x, int $z, Vector3 $place): bool
	{
		$start = $this->getCubicStart()->add($place)->subtract($this->getPoint());
		$end = $this->getCubicEnd()->add($place)->subtract($this->getPoint());

		return $start->getX() >> 4 <= $x && $x <= $end->getX() >> 4 && $start->getZ() >> 4 <= $z && $z <= $end->getZ() >> 4;
	}

	/**
	 * @param Vector3 $place
	 * @param Closure $closure
	 * @return void
	 */
	public function useOnBlocks(Vector3 $place, Closure $closure): void
	{
		Utils::validateCallableSignature(static function (int $x, int $y, int $z): void { }, $closure);
		$min = VectorUtils::enforceHeight($this->pos1->add($place));
		$max = VectorUtils::enforceHeight($this->pos2->add($place));
		for ($x = $min->getX(); $x <= $max->getX(); $x++) {
			for ($z = $min->getZ(); $z <= $max->getZ(); $z++) {
				for ($y = $min->getY(); $y <= $max->getY(); $y++) {
					$closure($x, $y, $z);
				}
			}
		}
	}

	/**
	 * @return Vector3
	 */
	public function getPoint(): Vector3
	{
		return $this->point;
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);

		$stream->putVector($this->point);
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);

		$this->point = $stream->getVector();
	}

	/**
	 * splits into 3x3 Chunk pieces
	 * @param Position $place
	 * @return DynamicBlockListSelection[]
	 */
	public function split(Position $place): array
	{
		if ($this->piece) {
			throw new UnexpectedValueException("Pieces are not split able");
		}

		//TODO: split tiles
		$pieces = [];
		for ($x = $this->pos1->getX() >> 4; $x <= $this->pos2->getX() >> 4; $x += 3) {
			for ($z = $this->pos1->getZ() >> 4; $z <= $this->pos2->getZ() >> 4; $z += 3) {
				$piece = new DynamicBlockListSelection($this->getPlayer(), $this->getPoint(), new Vector3(max($x << 4, $this->pos1->getX()), max($this->pos1->getY(), 0), max($z << 4, $this->pos1->getZ())), new Vector3(min(($x << 4) + 47, $this->pos2->getX()), min($this->pos2->getY(), Level::Y_MASK), min(($z << 4) + 47, $this->pos2->getZ())), true);
				for ($chunkX = 0; $chunkX < 3; $chunkX++) {
					for ($chunkZ = 0; $chunkZ < 3; $chunkZ++) {
						$piece->getManager()->setChunk($chunkX, $chunkZ, ($chunk = $this->getManager()->getChunk($x + $chunkX, $z + $chunkZ)));
						if ($chunk !== null) {
							$chunk->setX($chunkX);
							$chunk->setZ($chunkZ);
							$this->getManager()->setChunk($x + $chunkX, $z + $chunkZ);
						}
					}
				}
				$pieces[] = $piece;
			}
		}
		return $pieces;
	}
}