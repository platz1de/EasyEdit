<?php

namespace platz1de\EasyEdit\pattern\block;

use BadMethodCallException;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\world\ChunkController;
use pocketmine\block\Block;

/** Ignores Damage */
class MaskedBlockGroup extends BlockType
{
	private bool $invert = false;

	/**
	 * @param int[] $ids
	 */
	public function __construct(private array $ids)
	{
		parent::__construct();
	}

	/**
	 * @param int[] $ids
	 * @return self
	 */
	public static function inverted(array $ids): self
	{
		$instance = new self($ids);
		$instance->invert = true;
		return $instance;
	}

	public function equals(int $fullBlock): bool
	{
		return in_array($fullBlock >> Block::INTERNAL_STATE_DATA_BITS, $this->ids, true) !== $this->invert;
	}

	/**
	 * @param int             $x
	 * @param int             $y
	 * @param int             $z
	 * @param ChunkController $iterator
	 * @param Selection       $current
	 * @return int
	 */
	public function getFor(int $x, int &$y, int $z, ChunkController $iterator, Selection $current): int
	{
		throw new BadMethodCallException("Can't use MaskedBlockGroup for setting");
	}

	/**
	 * @param SelectionContext $context
	 */
	public function applySelectionContext(SelectionContext $context): void
	{
		throw new BadMethodCallException("Can't use MaskedBlockGroup for setting");
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putInt(count($this->ids));
		foreach ($this->ids as $id) {
			$stream->putInt($id);
		}
		$stream->putBool($this->invert);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->ids = [];
		for ($i = $stream->getInt(); $i > 0; $i--) {
			$this->ids[] = $stream->getInt();
		}
		$this->invert = $stream->getBool();
	}

	/**
	 * @return int[]
	 */
	public function getIds(): array
	{
		return $this->ids;
	}
}