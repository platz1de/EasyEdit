<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\VectorUtils;
use platz1de\EasyEdit\world\ReferencedWorldHolder;
use pocketmine\math\Vector3;
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
	protected bool $initialized = false;

	/**
	 * Selection constructor.
	 * @param string       $player
	 * @param string       $world
	 * @param Vector3|null $pos1
	 * @param Vector3|null $pos2
	 * @param bool         $piece
	 */
	public function __construct(string $player, string $world, ?Vector3 $pos1, ?Vector3 $pos2, bool $piece = false)
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
	 * @return int[]
	 */
	abstract public function getNeededChunks(): array;

	/**
	 * @param int $x
	 * @param int $z
	 * @return bool whether the chunk is in part of selected area (this doesn't check if it is actually affected)
	 */
	abstract public function isChunkOfSelection(int $x, int $z): bool;

	public function getPos1(): Vector3
	{
		return $this->pos1;
	}

	/**
	 * @param int $x
	 * @param int $z
	 * @return bool whether the chunk should be cached to aid in later executions
	 */
	abstract public function shouldBeCached(int $x, int $z): bool;

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
	 * @return Vector3
	 */
	public function getBottomCenter(): Vector3
	{
		return $this->getPos1()->addVector($this->getPos2())->divide(2)->withComponents(null, $this->getPos1()->getY(), null);
	}

	/**
	 * @param Closure          $closure
	 * @param SelectionContext $context
	 * @param Selection        $full
	 */
	abstract public function useOnBlocks(Closure $closure, SelectionContext $context, Selection $full): void;

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
			$pos = $this->pos1;
			$this->pos1 = VectorUtils::enforceHeight(Vector3::minComponents($this->pos1, $this->pos2));
			$this->pos2 = VectorUtils::enforceHeight(Vector3::maxComponents($pos, $this->pos2));
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
	 * @param Vector3 $place
	 */
	public function init(Vector3 $place): void
	{
		if ($this->initialized) {
			throw new UnexpectedValueException("Selection was already init");
		}
		$this->initialized = true;
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
	 * lowers the impact of Chunk loading
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
		$stream->putString(igbinary_serialize($this));
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
		/** @var Selection $selection */
		$selection = igbinary_unserialize($stream->getString());
		$selection->parseData($stream);
		return $selection;
	}

	public function __serialize(): array
	{
		return [$this->player];
	}

	public function __unserialize(array $data): void
	{
		$this->player = $data[0];
		$this->piece = false;
	}
}