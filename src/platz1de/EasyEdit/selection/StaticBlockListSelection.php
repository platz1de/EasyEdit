<?php

namespace platz1de\EasyEdit\selection;

use Closure;
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
		return parent::getNeededChunks(Position::fromObject(new Vector3(0, 0, 0), $this->getLevel()));
	}

	/**
	 * @param Vector3 $place
	 * @param Closure $closure
	 */
	public function useOnBlocks(Vector3 $place, Closure $closure): void
	{
		parent::useOnBlocks(new Vector3(), $closure);
	}
}