<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use Generator;
use platz1de\EasyEdit\math\BlockOffsetVector;
use platz1de\EasyEdit\math\BlockVector;
use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
use platz1de\EasyEdit\selection\identifier\SelectionIdentifier;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\world\ReferencedWorldHolder;
use pocketmine\math\Axis;

abstract class Selection implements SelectionIdentifier
{
	use ReferencedWorldHolder;

	protected BlockVector $pos1;
	protected BlockVector $pos2;
	protected BlockVector $selected1;
	protected BlockVector $selected2;

	/**
	 * Selection constructor.
	 * @param string           $world
	 * @param BlockVector|null $pos1
	 * @param BlockVector|null $pos2
	 */
	public function __construct(string $world, ?BlockVector $pos1, ?BlockVector $pos2)
	{
		$this->world = $world;

		if ($pos1 !== null) {
			$this->pos1 = clone($this->selected1 = $pos1);
		}
		if ($pos2 !== null) {
			$this->pos2 = clone($this->selected2 = $pos2);
		}

		$this->update();
	}

	/**
	 * @return int[]
	 */
	abstract public function getNeededChunks(): array;

	/**
	 * @return BlockVector
	 */
	public function getPos1(): BlockVector
	{
		return $this->pos1;
	}

	/**
	 * @return BlockVector
	 */
	public function getPos2(): BlockVector
	{
		return $this->pos2;
	}

	/**
	 * @return BlockOffsetVector
	 */
	public function getSize(): BlockOffsetVector
	{
		return $this->getPos2()->diff($this->getPos1())->cubicSize();
	}

	/**
	 * @return BlockVector
	 */
	public function getFloorCenter(): BlockVector
	{
		return new BlockVector((int) floor(($this->getPos1()->x + $this->getPos2()->x) / 2), (int) floor(($this->getPos1()->y + $this->getPos2()->y) / 2), (int) floor(($this->getPos1()->z + $this->getPos2()->z) / 2));
	}

	/**
	 * @return BlockVector
	 */
	public function getCeilCenter(): BlockVector
	{
		return new BlockVector((int) ceil(($this->getPos1()->x + $this->getPos2()->x) / 2), (int) ceil(($this->getPos1()->y + $this->getPos2()->y) / 2), (int) ceil(($this->getPos1()->z + $this->getPos2()->z) / 2));
	}

	/**
	 * @return BlockVector
	 */
	public function getBottomCenter(): BlockVector
	{
		return $this->getFloorCenter()->setComponent(Axis::Y, $this->getPos1()->y);
	}

	/**
	 * @param Closure          $closure
	 * @param SelectionContext $context
	 * @return Generator<ShapeConstructor>
	 */
	abstract public function asShapeConstructors(Closure $closure, SelectionContext $context): Generator;

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
			$temp = $this->pos1;
			$this->pos1 = BlockVector::minComponents($this->pos1, $this->pos2);
			$this->pos2 = BlockVector::maxComponents($temp, $this->pos2);
		}
	}

	/**
	 * @param BlockVector $pos1
	 */
	public function setPos1(BlockVector $pos1): void
	{
		$this->pos1 = clone($this->selected1 = $pos1);
		if (isset($this->selected2)) {
			$this->pos2 = clone($this->selected2);
		}

		$this->update();
	}

	/**
	 * @param BlockVector $pos2
	 */
	public function setPos2(BlockVector $pos2): void
	{
		if (isset($this->selected1)) {
			$this->pos1 = clone($this->selected1);
		}
		$this->pos2 = clone($this->selected2 = $pos2);

		$this->update();
	}

	/**
	 * @param BlockVector $pos
	 * @param int         $number
	 */
	public function setPos(BlockVector $pos, int $number): void
	{
		//TODO: Selections really shouldn't be restricted to two points
		if ($number === 1) {
			$this->setPos1($pos);
		} else {
			$this->setPos2($pos);
		}
	}

	public function asSelection(): Selection
	{
		return $this;
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putBlockVector($this->pos1);
		$stream->putBlockVector($this->pos2);
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->pos1 = $stream->getBlockVector();
		$this->pos2 = $stream->getBlockVector();
	}

	/**
	 * @return string
	 */
	public function fastSerialize(): string
	{
		$stream = new ExtendedBinaryStream();
		$stream->putString(igbinary_serialize($this) ?? "");
		$this->putData($stream);
		return $stream->getBuffer();
	}

	/**
	 * @param string $data
	 * @return static
	 */
	public static function fastDeserialize(string $data): static
	{
		$stream = new ExtendedBinaryStream($data);
		/** @var static $selection */
		$selection = igbinary_unserialize($stream->getString());
		$selection->parseData($stream);
		return $selection;
	}

	/**
	 * @return array{string}
	 */
	public function __serialize(): array
	{
		return [$this->world];
	}

	/**
	 * @param array{string} $data
	 * @return void
	 */
	public function __unserialize(array $data): void
	{
		$this->world = $data[0];
	}
}