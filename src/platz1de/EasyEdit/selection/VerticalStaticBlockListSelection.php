<?php

namespace platz1de\EasyEdit\selection;

use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class VerticalStaticBlockListSelection extends StaticBlockListSelection
{
	public function addBlock(int $x, int $y, int $z, int $id, bool $overwrite = true): void
	{
		parent::addBlock($x, $y, $z, $id, $overwrite);
		$this->pos1->withComponents(null, min($y, $this->pos1->y), null);
		$this->pos2->withComponents(null, max($y, $this->pos2->y), null);
	}

	//NOTE: this selection is split into static block lists
}