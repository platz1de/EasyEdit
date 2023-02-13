<?php

namespace platz1de\EasyEdit\convert\block;

use platz1de\EasyEdit\utils\BlockParser;
use platz1de\EasyEdit\utils\RepoManager;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\nbt\tag\Tag;
use UnexpectedValueException;

/**
 * Combines multiple states into multiple different ones
 */
class CombinedMultiStateTranslator extends SingularStateTranslator
{
	private array $combinedStates;
	private array $combinedStateData;

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
	}

	public function translate(BlockStateData $state): BlockStateData
	{
		$state = parent::translate($state);
		$states = $state->getStates();
		$add = $this->getCombinedState($states, $this->combinedStateData, $this->combinedStates);
		foreach ($this->combinedStates as $combinedState) {
			unset($states[$combinedState]);
		}
		foreach ($add as $name => $value) {
			if (isset($states[$name])) {
				throw new UnexpectedValueException("State $name already exists");
			}
			$states[$name] = clone $value;
		}
		return new BlockStateData($state->getName(), $states, RepoManager::getVersion());
	}

	private function parseCombinedStates(array $states, array &$target): void
	{
		foreach ($states as $name => $state) {
			if (is_array($state[array_key_first($state)] ?? null)) {
				$this->parseCombinedStates($state, $target[$name]);
			} else {
				$target[$name] = [];
				foreach ($state as $value => $tag) {
					$target[$name][$value] = BlockParser::tagFromStringValue($tag);
				}
			}
		}
	}

	/**
	 * @param string[] $left
	 * @return Tag[]
	 */
	private function getCombinedState(array $states, array $current, array $left): array
	{
		$state = array_shift($left);
		if (!isset($states[$state])) {
			throw new UnexpectedValueException("Missing state $state");
		}

		$stateValue = BlockParser::tagToStringValue($states[$state]);
		if (!isset($current[$stateValue])) {
			throw new UnexpectedValueException("Missing state $stateValue");
		}
		$value = $current[$stateValue];
		if (is_array($value)) {
			return $this->getCombinedState($states, $value, $left);
		}
		if (!is_array($value)) {
			throw new UnexpectedValueException("Invalid state $stateValue");
		}
		return $value;
	}
}