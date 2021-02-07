<?php

namespace platz1de\EasyEdit\selection;

use platz1de\EasyEdit\task\ReferencedChunkManager;
use pocketmine\level\format\Chunk;
use pocketmine\level\Position;
use pocketmine\math\Vector3;

class StaticBlockListSelection extends BlockListSelection
{
	/**
	 * @param Position $place
	 * @return array
	 */
	public function getNeededChunks(Position $place): array
	{
		return parent::getNeededChunks(Position::fromObject(new Vector3(0, 0, 0), $place->getLevel()));
	}

	public function getAffectedBlocks(Vector3 $place): array
	{
		return parent::getAffectedBlocks(new Vector3());
	}
}