<?php

namespace platz1de\EasyEdit\convert\block;

use platz1de\EasyEdit\utils\BlockParser;
use platz1de\EasyEdit\utils\RepoManager;
use pocketmine\data\bedrock\block\BlockStateData;
use UnexpectedValueException;

/**
 * Always results in the same block type
 */
abstract class BaseStateTranslator extends BlockStateTranslator
{
	private array $removedStates;
	private array $addedStates = [];
	private array $valueReplacements = [];

	public function __construct(array $data)
	{
		parent::__construct($data);
		$added = $data["state_additions"] ?? [];
		foreach ($added as $state => $value) {
			$this->addedStates[$state] = BlockParser::tagFromStringValue($value);
		}
		$this->removedStates = $data["state_removals"] ?? [];
		$replace = $data["state_values"] ?? [];
		foreach ($replace as $stateName => $values) {
			$this->valueReplacements[$stateName] = [];
			foreach ($values as $value) {
				$this->valueReplacements[$stateName][$value] = BlockParser::tagFromStringValue($value);
			}
		}
	}

	public function translate(BlockStateData $state): BlockStateData
	{
		$states = $state->getStates();
		foreach ($this->removedStates as $removedState) {
			unset($states[$removedState]);
		}
		foreach ($this->addedStates as $addedState => $addedValue) {
			$states[$addedState] = clone $addedValue;
		}
		foreach ($states as $stateName => $stateValue) {
			if (isset($this->valueReplacements[$stateName])) {
				$states[$stateName] = clone $this->valueReplacements[$stateName][BlockParser::tagToStringValue($stateValue)] ?? $stateValue;
			}
		}
		return new BlockStateData($state->getName(), $states, RepoManager::getVersion());
	}
}