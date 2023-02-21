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
	 * @var array<string, string>
	 */
	private array $renamedStates = [];

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

		$renames = $data["state_renames"] ?? [];
		if (!is_array($renames)) {
			throw new UnexpectedValueException("state_renames must be an array");
		}
		foreach ($renames as $old => $new) {
			if (!is_string($old) || !is_string($new)) {
				throw new UnexpectedValueException("state_renames must be an array of strings");
			}
			$this->renamedStates[$old] = $new;
		}
	}

	/**
	 * @param BlockStateData $state
	 * @return BlockStateData
	 */
	public function translate(BlockStateData $state): BlockStateData
	{
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
		return parent::translate(new BlockStateData($this->targetState, $states, RepoManager::getVersion()));
	}
}