<?php

namespace platz1de\EasyEdit\convert\block;

use platz1de\EasyEdit\utils\BlockParser;
use pocketmine\data\bedrock\block\BlockStateData;

abstract class BlockStateTranslator
{
	private array $defaults = [];

	public function __construct($data)
	{
		if (isset($data["defaults"])) {
			foreach ($data["defaults"] as $state => $value) {
				$this->defaults[$state] = BlockParser::tagFromStringValue($value);
			}
		}
	}

	abstract public function translate(BlockStateData $state): BlockStateData;

	public function applyDefaults(BlockStateData $state): BlockStateData
	{
		$states = $state->getStates();
		foreach ($this->defaults as $stateName => $stateValue) {
			if (!isset($states[$stateName])) {
				$states[$stateName] = clone $stateValue;
			}
		}
		return new BlockStateData($state->getName(), $states, $state->getVersion());
	}
}