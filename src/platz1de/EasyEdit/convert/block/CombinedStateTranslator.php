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
	/**
	 * @var string[]
	 */
	private array $combinedStates;
	/**
	 * @var array<string, mixed>
	 */
	private array $combinedStateData = [];
	private Tag $defaultState;
	private string $resultState;

	/**
	 * @param array<string, mixed> $data
	 */
	public function __construct(array $data)
	{
		parent::__construct($data);
		if (!isset($data["combined_names"]) || !is_array($data["combined_names"])) {
			throw new UnexpectedValueException("Missing combined_name");
		}
		$this->combinedStates = $data["combined_names"];

		if (!isset($data["combined_states"]) || !is_array($data["combined_states"])) {
			throw new UnexpectedValueException("Missing combined_states");
		}
		$combinedStates = $data["combined_states"];
		if (isset($combinedStates["default"])) {
			$this->defaultState = BlockParser::tagFromStringValue($combinedStates["default"]);
			unset($combinedStates["default"]);
		}
		$this->parseCombinedStates($combinedStates, $this->combinedStateData);

		if (!isset($data["target_name"]) || !is_string($data["target_name"])) {
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
		try {
			$states[$this->resultState] = $this->getCombinedState($states, $this->combinedStateData, $this->combinedStates);
		} catch (UnexpectedValueException $e) {
			if (isset($this->defaultState)) {
				$states[$this->resultState] = $this->defaultState;
			} else {
				throw $e;
			}
		}
		foreach ($this->combinedStates as $combinedState) {
			unset($states[$combinedState]);
		}
		return new BlockStateData($state->getName(), $states, RepoManager::getVersion());
	}

	/**
	 * @param array<string, mixed> $states
	 * @param array<string, mixed> $target
	 */
	private function parseCombinedStates(array $states, array &$target): void
	{
		foreach ($states as $name => $state) {
			if (is_array($state)) {
				if (!isset($target[$name])) {
					$target[$name] = [];
				}
				$this->parseCombinedStates($state, $target[$name]);
			} elseif (is_string($state)) {
				$target[$name] = BlockParser::tagFromStringValue($state);
			} else {
				throw new UnexpectedValueException("Invalid state $name");
			}
		}
	}

	/**
	 * @param Tag[]                $states
	 * @param array<string, mixed> $current
	 * @param string[]             $left
	 * @return Tag
	 */
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
		if (!($current[$stateValue] instanceof Tag)) {
			throw new UnexpectedValueException("Invalid state $stateValue");
		}
		return clone $current[$stateValue];
	}
}