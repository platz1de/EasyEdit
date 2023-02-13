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
	private array $renamedStates = [];

	public function __construct(array $data)
	{
		parent::__construct($data);
		if (!isset($data["name"])) {
			throw new UnexpectedValueException("Missing name");
		}
		$this->targetState = $data["name"];
		$this->renamedStates = $data["state_renames"] ?? [];
	}

	public function translate(BlockStateData $state): BlockStateData
	{
		$state = parent::translate($state);
		$states = $state->getStates();
		foreach ($this->renamedStates as $old => $new) {
			if (isset($states[$old])) {
				if (isset($states[$new])) {
					throw new UnexpectedValueException("State $new already exists");
				}
				$states[$new] = $states[$old];
				unset($states[$old]);
			}
		}
		return new BlockStateData($this->targetState, $states, RepoManager::getVersion());
	}
}