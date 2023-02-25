<?php

namespace platz1de\EasyEdit\convert\block;

use platz1de\EasyEdit\utils\BlockParser;
use platz1de\EasyEdit\utils\RepoManager;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\nbt\tag\Tag;
use UnexpectedValueException;

/**
 * Always results in the same block type
 */
abstract class BaseStateTranslator extends BlockStateTranslator
{
	/**
	 * @var string[]
	 */
	private array $removedStates;
	/**
	 * @var array<string, Tag>
	 */
	private array $addedStates = [];
	/**
	 * @var array<string, array<string, Tag>>
	 */
	private array $valueReplacements = [];
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

		$added = $data["state_additions"] ?? [];
		if (!is_array($added)) {
			throw new UnexpectedValueException("state_additions must be an array");
		}
		foreach ($added as $state => $value) {
			$this->addedStates[$state] = BlockParser::tagFromStringValue($value);
		}

		$removed = $data["state_removals"] ?? [];
		if (!is_array($removed)) {
			throw new UnexpectedValueException("state_removals must be an array");
		}
		$this->removedStates = $removed;

		$replace = $data["state_values"] ?? [];
		if (!is_array($replace)) {
			throw new UnexpectedValueException("state_values must be an array");
		}
		foreach ($replace as $stateName => $values) {
			$this->valueReplacements[$stateName] = [];
			foreach ($values as $key => $value) {
				$this->valueReplacements[$stateName][(string) $key] = BlockParser::tagFromStringValue($value);
			}
		}

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

	public function translate(BlockStateData $state): BlockStateData
	{
		$states = $state->getStates();
		foreach ($this->removedStates as $removedState) {
			unset($states[$removedState]);
		}
		foreach ($this->renamedStates as $old => $new) {
			if (isset($states[$old])) {
				if (isset($states[$new])) {
					throw new UnexpectedValueException("State $new already exists");
				}
				$states[$new] = $states[$old];
				unset($states[$old]);
			}
		}
		foreach ($this->addedStates as $addedState => $addedValue) {
			$states[$addedState] = clone $addedValue;
		}
		foreach ($states as $stateName => $stateValue) {
			if (isset($this->valueReplacements[$stateName])) {
				$states[$stateName] = clone($this->valueReplacements[$stateName][BlockParser::tagToStringValue($stateValue)] ?? $stateValue);
			}
		}
		return new BlockStateData($state->getName(), $states, RepoManager::getVersion());
	}
}