<?php

namespace platz1de\EasyEdit\task\editing\selection;

use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\task\editing\EditTaskHandler;
use platz1de\EasyEdit\task\editing\selection\cubic\CubicStaticUndo;
use platz1de\EasyEdit\task\editing\type\SettingNotifier;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\block\Block;
use pocketmine\block\BlockLegacyIds;
use pocketmine\math\Vector3;
use pocketmine\utils\Random;
use pocketmine\world\generator\noise\Noise;
use pocketmine\world\generator\noise\Simplex;

class Noise3DTask extends SelectionEditTask
{
	use CubicStaticUndo;
	use SettingNotifier;

	private int $octaves;
	private float $persistence;
	private float $expansion;
	private float $threshold;
	private Noise $noise;

	/**
	 * @param Selection $selection
	 * @param int       $octaves
	 * @param float     $persistence
	 * @param float     $expansion
	 * @param float     $threshold
	 */
	public function __construct(Selection $selection, int $octaves = 4, float $persistence = 0.25, float $expansion = 0.05, float $threshold = 0)
	{
		$this->octaves = $octaves;
		$this->persistence = $persistence;
		$this->expansion = $expansion;
		$this->threshold = $threshold;
		parent::__construct($selection);
	}

	/**
	 * @return string
	 */
	public function getTaskName(): string
	{
		return "noise_3d";
	}

	public function executeEdit(EditTaskHandler $handler, int $chunk): void
	{
		if (!isset($this->noise)) {
			$this->noise = new Simplex(new Random(time()), $this->octaves, $this->persistence, $this->expansion);
		}
		$selection = $this->selection;
		$size = $selection->getSize()->subtract(1, 1, 1);
		$noise = $this->noise->getFastNoise3D($size->getFloorX(), $size->getFloorY(), $size->getFloorZ(), 1, 1, 1, $selection->getPos1()->getFloorX(), $selection->getPos1()->getFloorY(), $selection->getPos1()->getFloorZ());
		$selection->asShapeConstructors(function (int $x, int $y, int $z) use ($selection, $handler, $noise): void {
			if ($noise[$x - $selection->getPos1()->getFloorX()][$z - $selection->getPos1()->getFloorZ()][$y - $selection->getPos1()->getFloorY()] > $this->threshold) {
				$handler->changeBlock($x, $y, $z, BlockLegacyIds::STONE << Block::INTERNAL_METADATA_BITS);
			} else {
				$handler->changeBlock($x, $y, $z, 0);
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