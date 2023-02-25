<?php

namespace platz1de\EasyEdit\convert\block;

use platz1de\EasyEdit\utils\RepoManager;
use pocketmine\data\bedrock\block\BlockStateData;
use UnexpectedValueException;

/**
 * Always results in the same block type
 */
class SingularStateTranslator extends BaseStateTranslator
{
	private string $targetState;

	/**
	 * @param array<string, mixed> $data
	 */
	public function __construct(array $data)
	{
		parent::__construct($data);
		if (!isset($data["name"]) || !is_string($data["name"])) {
			throw new UnexpectedValueException("Missing name");
		}
		$this->targetState = $data["name"];
	}

	/**
	 * @param BlockStateData $state
	 * @return BlockStateData
	 */
	public function translate(BlockStateData $state): BlockStateData
	{
		return parent::translate(new BlockStateData($this->targetState, $state->getStates(), RepoManager::getVersion()));
	}
}