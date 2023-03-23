<?php

namespace platz1de\EasyEdit\selection;

use pocketmine\math\Axis;

class VerticalStaticBlockListSelection extends StaticBlockListSelection
{
	public function addBlock(int $x, int $y, int $z, int $id, bool $overwrite = true): void
	{
		parent::addBlock($x, $y, $z, $id, $overwrite);
		$this->pos1 = $this->pos1->setComponent(Axis::Y, min($y, $this->pos1->y));
		$this->pos2 = $this->pos2->setComponent(Axis::Y, max($y, $this->pos2->y));
	}

	//NOTE: this selection is split into static block lists
}