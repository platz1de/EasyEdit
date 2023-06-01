<?php

namespace platz1de\EasyEdit\task\editing;

use Generator;
use platz1de\EasyEdit\selection\constructor\ShapeConstructor;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\task\editing\cubic\CubicStaticUndo;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\utils\Random;
use pocketmine\world\generator\noise\Noise;
use pocketmine\world\generator\noise\Simplex;

class Noise3DTask extends SelectionEditTask
{
	use CubicStaticUndo;

	private Noise $noise;

	/**
	 * @param Selection $selection
	 * @param int       $octaves
	 * @param float     $persistence
	 * @param float     $expansion
	 * @param float     $threshold
	 */
	public function __construct(Selection $selection, private int $octaves = 4, private float $persistence = 0.25, private float $expansion = 0.05, private float $threshold = 0)
	{
		parent::__construct($selection);
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "noise_3d";
	}

	/**
	 * @param EditTaskHandler $handler
	 * @return Generator<ShapeConstructor>
	 */
	public function prepareConstructors(EditTaskHandler $handler): Generator
	{
		if (!isset($this->noise)) {
			$this->noise = new Simplex(new Random(time()), $this->octaves, $this->persistence, $this->expansion);
		}
		$selection = $this->selection;
		$size = $selection->getSize();
		$noise = $this->noise->getFastNoise3D($size->x - 1, $size->y - 1, $size->z - 1, 1, 1, 1, $selection->getPos1()->x, $selection->getPos1()->y, $selection->getPos1()->z);
		$stone = VanillaBlocks::STONE()->getStateId();
		$air = VanillaBlocks::AIR()->getStateId();
		yield from $selection->asShapeConstructors(function (int $x, int $y, int $z) use ($air, $stone, $selection, $handler, $noise): void {
			if ($noise[$x - $selection->getPos1()->x][$z - $selection->getPos1()->z][$y - $selection->getPos1()->y] > $this->threshold) {
				$handler->changeBlock($x, $y, $z, $stone);
			} else {
				$handler->changeBlock($x, $y, $z, $air);
			}
		}, $this->context);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);
		$stream->putInt($this->octaves);
		$stream->putFloat($this->persistence);
		$stream->putFloat($this->expansion);
		$stream->putFloat($this->threshold);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);
		$this->octaves = $stream->getInt();
		$this->persistence = $stream->getFloat();
		$this->expansion = $stream->getFloat();
		$this->threshold = $stream->getFloat();
	}
}