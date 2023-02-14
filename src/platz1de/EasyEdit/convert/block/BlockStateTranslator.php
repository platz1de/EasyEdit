<?php

namespace platz1de\EasyEdit\convert\block;

use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\utils\BlockParser;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\nbt\tag\Tag;
use UnexpectedValueException;

abstract class BlockStateTranslator
{
	/**
	 * @var array<string, Tag>
	 */
	private array $defaults = [];
	/**
	 * @var array<string, string[]>
	 */
	private array $values = [];

	/**
	 * @param array<string, mixed> $data
	 */
	public function __construct(array $data)
	{
		if (isset($data["defaults"])) {
			$defaults = $data["defaults"];
			if (!is_array($defaults)) {
				throw new UnexpectedValueException("defaults must be an array");
			}
			foreach ($defaults as $state => $value) {
				$this->defaults[$state] = BlockParser::tagFromStringValue($value);
			}
		}

		if (isset($data["values"])) {
			$values = $data["values"];
			if (!is_array($values)) {
				throw new UnexpectedValueException("values must be an array");
			}
			foreach ($values as $state => $value) {
				$this->values[$state] = $value;
			}
		}
	}

	abstract public function translate(BlockStateData $state): BlockStateData;

	public function applyDefaults(BlockStateData $state): BlockStateData
	{
		$states = $state->getStates();
		if ($this->values !== []) {
			foreach ($states as $stateName => $stateValue) {
				if (isset($this->values[$stateName])) {
					$stateValue = BlockParser::tagToStringValue($stateValue);
					if (in_array($stateValue, $this->values[$stateName], true)) {
						continue;
					}
					EditThread::getInstance()->debug("State $stateName is $stateValue, but should be one of " . implode(", ", $this->values[$stateName]));
					unset($states[$stateName]);
				} else {
					EditThread::getInstance()->debug("Unknown state $stateName");
					unset($states[$stateName]);
				}
			}
		}
		foreach ($this->defaults as $stateName => $stateValue) {
			if (!isset($states[$stateName])) {
				$states[$stateName] = clone $stateValue;
			}
		}
		return new BlockStateData($state->getName(), $states, $state->getVersion());
	}

	/**
	 * @return int[]
	 */
	public function getAllPossibleStates(string $name): array
	{
		return []; //TODO
	}
}