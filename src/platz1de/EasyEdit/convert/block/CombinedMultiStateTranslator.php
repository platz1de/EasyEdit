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
	/**
	 * @var string[]
	 */
	private array $combinedStates;
	/**
	 * @var array<string, mixed>
	 */
	private array $combinedStateData = [];

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
		$this->parseCombinedStates($data["combined_states"], $this->combinedStateData);
	}

	/**
	 * @param BlockStateData $state
	 * @return BlockStateData
	 */
	public function translate(BlockStateData $state): BlockStateData
	{
		$state = parent::translate($state);
		$states = $state->getStates();
		$add = $this->getCombinedStates($states, $this->combinedStateData, $this->combinedStates);
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

	/**
	 * @param array<string, array<string, mixed>> $states
	 * @param array<string, array<string, mixed>> $target
	 */
	private function parseCombinedStates(array $states, array &$target): void
	{
		foreach ($states as $name => $state) {
			if (is_array($state[array_key_first($state)])) {
				if (!isset($target[$name])) {
					$target[$name] = [];
				}
				/** @var array<array<string, mixed>> $state */
				$this->parseCombinedStates($state, $target[$name]);
			} else {
				$target[$name] = [];
				foreach ($state as $value => $tag) {
					if (!is_string($tag)) {
						throw new UnexpectedValueException("Invalid tag for $name");
					}
					$target[$name][$value] = BlockParser::tagFromStringValue($tag);
				}
			}
		}
	}

	/**
	 * @param Tag[]                $states
	 * @param array<string, mixed> $current
	 * @param string[]             $left
	 * @return Tag[]
	 */
	private function getCombinedStates(array $states, array $current, array $left): array
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
		if (!is_array($value)) {
			throw new UnexpectedValueException("Invalid state $stateValue");
		}
		if ($left !== []) {
			return $this->getCombinedStates($states, $value, $left);
		}
		return $value;
	}
}