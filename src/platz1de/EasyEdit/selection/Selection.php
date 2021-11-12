<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\ReferencedWorldHolder;
use pocketmine\math\Vector3;
use pocketmine\world\World;
use UnexpectedValueException;

abstract class Selection
{
	use ReferencedWorldHolder;

	protected Vector3 $pos1;
	protected Vector3 $pos2;
	protected Vector3 $selected1;
	protected Vector3 $selected2;
	protected string $player;
	protected bool $piece;

	/**
	 * Selection constructor.
	 * @param string       $player
	 * @param string       $world
	 * @param Vector3|null $pos1
	 * @param Vector3|null $pos2
	 * @param bool         $piece
	 */
	public function __construct(string $player, string $world = "", ?Vector3 $pos1 = null, ?Vector3 $pos2 = null, bool $piece = false)
	{
		$this->world = $world;

		if ($pos1 !== null) {
			$this->pos1 = clone($this->selected1 = $pos1->floor());
		}
		if ($pos2 !== null) {
			$this->pos2 = clone($this->selected2 = $pos2->floor());
		}

		$this->player = $player;
		$this->piece = $piece;

		$this->update();
	}

	/**
	 * @param Vector3 $place
	 * @return int[]
	 */
	abstract public function getNeededChunks(Vector3 $place): array;

	/**
	 * @param int     $x
	 * @param int     $z
	 * @param Vector3 $place
	 * @return bool whether the chunk is in part of selected area (this doesn't check if it is actually affected)
	 */
	abstract public function isChunkOfSelection(int $x, int $z, Vector3 $place): bool;

	/**
	 * @return Vector3
	 */
	public function getCubicStart(): Vector3
	{
		return $this->getPos1();
	}

	/**
	 * @return Vector3
	 */
	public function getCubicEnd(): Vector3
	{
		return $this->getPos2();
	}

	/**
	 * @return Vector3
	 */
	public function getSize(): Vector3
	{
		return $this->getPos2()->subtractVector($this->getPos1())->add(1, 1, 1);
	}

	/**
	 * @param Vector3          $place
	 * @param Closure          $closure
	 * @param SelectionContext $context
	 * @param Selection        $full
	 */
	abstract public function useOnBlocks(Vector3 $place, Closure $closure, SelectionContext $context, Selection $full): void;

	/**
	 * @return bool
	 */
	public function isValid(): bool
	{
		return isset($this->pos1, $this->pos2);
	}

	/**
	 * calculating the "real" positions (selected ones don't have to be the smallest and biggest
	 * they could be mixed)
	 */
	protected function update(): void
	{
		if ($this->isValid()) {
			$minX = min($this->pos1->getX(), $this->pos2->getX());
			$maxX = max($this->pos1->getX(), $this->pos2->getX());
			$minY = max(min($this->pos1->getY(), $this->pos2->getY()), World::Y_MIN);
			$maxY = min(max($this->pos1->getY(), $this->pos2->getY()), World::Y_MAX - 1);
			$minZ = min($this->pos1->getZ(), $this->pos2->getZ());
			$maxZ = max($this->pos1->getZ(), $this->pos2->getZ());

			$this->pos1 = new Vector3($minX, $minY, $minZ);
			$this->pos2 = new Vector3($maxX, $maxY, $maxZ);
		}
	}

	/**
	 * @param Vector3 $pos1
	 */
	public function setPos1(Vector3 $pos1): void
	{
		$this->pos1 = clone($this->selected1 = $pos1);
		if (isset($this->selected2)) {
			$this->pos2 = clone($this->selected2);
		}

		$this->update();
	}

	/**
	 * @param Vector3 $pos2
	 */
	public function setPos2(Vector3 $pos2): void
	{
		if (isset($this->selected1)) {
			$this->pos1 = clone($this->selected1);
		}
		$this->pos2 = clone($this->selected2 = $pos2);

		$this->update();
	}

	/**
	 * @return Vector3
	 */
	public function getPos1(): Vector3
	{
		return $this->pos1;
	}

	/**
	 * @return Vector3
	 */
	public function getPos2(): Vector3
	{
		return $this->pos2;
	}

	/**
	 * @return string
	 */
	public function getPlayer(): string
	{
		return $this->player;
	}

	public function close(): void
	{
	}

	/**
	 * @param Selection   $selection
	 * @param string|null $expected
	 */
	public static function validate(Selection $selection, ?string $expected = null): void
	{
		if (($expected !== null) && get_class($selection) !== $expected) {
			throw new WrongSelectionTypeError(get_class($selection), $expected);
		}
		if (!$selection->isValid()) {
			throw new UnexpectedValueException("Selection is not valid");
		}
	}

	/**
	 * @param int $block
	 * @return bool
	 */
	public static function processBlock(int &$block): bool
	{
		$return = ($block !== 0);

		if ($block === 0xD90) { //structure_void
			$block = 0;
		}

		return $return;
	}

	/**
	 * Splits the selection into smaller parts
	 * lower the impact of Chunk loading
	 * @param Vector3 $offset
	 * @return Selection[]
	 */
	public function split(Vector3 $offset): array
	{
		return [$this];
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->world);

		$stream->putVector($this->pos1);
		$stream->putVector($this->pos2);
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->world = $stream->getString();

		$this->pos1 = $stream->getVector();
		$this->pos2 = $stream->getVector();
	}

	/**
	 * @return string
	 */
	public function fastSerialize(): string
	{
		$stream = new ExtendedBinaryStream();
		$stream->putString(static::class);
		$stream->putString($this->player);
		$this->putData($stream);
		return $stream->getBuffer();
	}

	/**
	 * @param string $data
	 * @return Selection
	 */
	public static function fastDeserialize(string $data): Selection
	{
		$stream = new ExtendedBinaryStream($data);
		$type = $stream->getString();
		/** @var Selection $selection */
		$selection = new $type($stream->getString());
		$selection->parseData($stream);
		return $selection;
	}
}