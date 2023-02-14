<?php

namespace platz1de\EasyEdit\convert\block;

use platz1de\EasyEdit\utils\RepoManager;
use pocketmine\data\bedrock\block\BlockStateData;

/**
 * Literally not translating at all
 */
class ReplicaStateTranslator extends BlockStateTranslator
{
	/**
	 * @param BlockStateData $state
	 * @return BlockStateData
	 */
	public function translate(BlockStateData $state): BlockStateData
	{
		return new BlockStateData($state->getName(), $state->getStates(), RepoManager::getVersion());
	}
}