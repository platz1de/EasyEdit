<?php

namespace platz1de\EasyEdit\selection;

use platz1de\EasyEdit\task\ReferencedChunkManager;
use pocketmine\level\format\Chunk;
use pocketmine\math\Vector3;

class DynamicBlockListSelection extends BlockListSelection
{
	public function __construct(string $player, int $xSize, int $ySize, int $zSize)
	{
		parent::__construct($player, new ReferencedChunkManager(""), $xSize, $ySize, $zSize);
		$this->getManager()->load(new Vector3(0, 0, 0), $xSize, $zSize);
	}
}