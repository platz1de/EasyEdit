<?php

namespace platz1de\EasyEdit\convert\block;

use platz1de\EasyEdit\utils\BlockParser;
use platz1de\EasyEdit\utils\RepoManager;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\nbt\tag\Tag;
use UnexpectedValueException;

/**
 * Combines at least two states into a single one
 */
class CombinedStateTranslator extends SingularStateTranslator
{
	private array $combinedStates;
	private array $combinedStateData;
	private string $resultState;

	public function __construct(array $data)
	{
		parent::__construct($data);
		if (!isset($data["combined_names"])) {
			throw new UnexpectedValueException("Missing combined_name");
		}
		$this->combinedStates = $data["combined_names"];
		if (!isset($data["combined_states"])) {
			throw new UnexpectedValueException("Missing combined_states");
		}
		$this->parseCombinedStates($data["combined_states"], $this->combinedStateData);
		if (!isset($data["target_name"])) {
			throw new UnexpectedValueException("Missing target_name");
		}
		$this->resultState = $data["target_name"];
	}

	public function translate(BlockStateData $state): BlockStateData
	{
		$state = parent::translate($state);
		$states = $state->getStates();
		if (isset($states[$this->resultState])) {
			throw new UnexpectedValueException("State $this->resultState already exists");
		}
		$states[$this->resultState] = $this->getCombinedState($states, $this->combinedStateData, $this->combinedStates);
		foreach ($this->combinedStates as $combinedState) {
			unset($states[$combinedState]);
		}
		return new BlockStateData($state->getName(), $states, RepoManager::getVersion());
	}

	private function parseCombinedStates(array $states, array &$target): void
	{
		foreach ($states as $name => $state) {
			if (is_array($state)) {
				$this->parseCombinedStates($state, $target[$name]);
			} else {
				$target[$name] = BlockParser::tagFromStringValue($state);
			}
		}
	}

	private function getCombinedState(array $states, array $current, array $left): Tag
	{
		$state = array_shift($left);
		if (!isset($states[$state])) {
			throw new UnexpectedValueException("Missing state $state");
		}
		$stateValue = BlockParser::tagToStringValue($states[$state]);
		if (!isset($current[$stateValue])) {
			throw new UnexpectedValueException("Missing state $stateValue");
		}
		if (is_array($current[$stateValue])) {
			return $this->getCombinedState($states, $current[$stateValue], $left);
		}
		return clone $current[$stateValue];
	}
}