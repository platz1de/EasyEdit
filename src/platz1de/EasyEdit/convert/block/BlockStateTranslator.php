<?php

namespace platz1de\EasyEdit\convert\block;

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