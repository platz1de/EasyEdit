<?php

namespace platz1de\EasyEdit\pattern\block;

/** Ignores Damage */
class DynamicBlock extends StaticBlock
{
	/**
	 * @return int
	 */
	public function getMeta(): int
	{
		return -1;
	}
}