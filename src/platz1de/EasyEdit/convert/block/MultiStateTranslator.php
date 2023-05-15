<?php

namespace platz1de\EasyEdit\convert\block;

use platz1de\EasyEdit\utils\BlockParser;
use platz1de\EasyEdit\utils\RepoManager;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\nbt\tag\Tag;
use UnexpectedValueException;

/**
 * Results in multiple block types, differentiated by a single state
 */
class MultiStateTranslator extends SimpleStateTranslator
{
	/**
	 * @var array<string, mixed>
	 */
	private array $mapping = [];
	/**
	 * @var string[]
	 */
	private array $identifier = [];

	/**
	 * @param array<string, mixed> $data
	 */
	public function __construct(array $data)
	{
		parent::__construct($data);

		if (!isset($data["identifier"]) || !is_array($data["identifier"])) {
			throw new UnexpectedValueException("Missing multi_name");
		}
		foreach ($data["identifier"] as $value) {
			if (!is_string($value)) {
				throw new UnexpectedValueException("identifier must be a string");
			}
			$this->identifier[] = $value;
		}

		$mapping = $data["mapping"] ?? [];
		if (!is_array($mapping)) {
			throw new UnexpectedValueException("mapping must be an array");
		}
		$this->parseMappingEntries($mapping, $this->mapping, count($this->identifier));
	}

	/**
	 * @param array<string, mixed> $mapping
	 * @param array<string, mixed> $target
	 * @param int                  $depth
	 */
	private function parseMappingEntries(array $mapping, array &$target, int $depth): void
	{
		$depth--;
		if ($depth === 0) {
			foreach ($mapping as $name => $state) {
				if (!is_array($state)) {
					throw new UnexpectedValueException("Invalid mapping entry $name");
				}
				$target[$name] = new SimpleStateTranslator($state);
			}
			return;
		}
		foreach ($mapping as $name => $state) {
			if (!is_array($state)) {
				throw new UnexpectedValueException("Invalid mapping entry $name");
			}
			if ($name === "def") {
				$target["def"] = $state;
				continue;
			}
			$target[$name] = [];
			$this->parseMappingEntries($state, $target[$name], $depth);
		}
	}

	/**
	 * @param array<string, Tag>   $states
	 * @param array<string, mixed> $current
	 * @param string[]             $left
	 * @return SimpleStateTranslator
	 */
	private function getMappedState(array $states, array $current, array $left): SimpleStateTranslator
	{
		$state = array_shift($left);
		if (!isset($states[$state])) {
			throw new UnexpectedValueException("Missing state $state");
		}
		$stateValue = BlockParser::tagToStringValue($states[$state]);
		$next = $current[$stateValue] ?? $current["def"] ?? $this->mapping["def"] ?? null;
		if ($next === null) {
			throw new UnexpectedValueException("Missing state $stateValue");
		}
		if ($left !== []) {
			if (!is_array($next)) {
				throw new UnexpectedValueException("Invalid state $stateValue");
			}
			return $this->getMappedState($states, $next, $left);
		}
		if (!$next instanceof SimpleStateTranslator) {
			throw new UnexpectedValueException("Invalid state $stateValue");
		}
		return clone $next;
	}

	public function translate(BlockStateData $state): BlockStateData
	{
		$state = $this->applyDefaultTileData($state);

		$states = $state->getStates();
		$states = $this->process($states);

		$mapped = $this->getMappedState($state->getStates(), $this->mapping, $this->identifier);
		$states = $mapped->process($states, $this); //mapped has priority

		return new BlockStateData($mapped->targetState ?? $this->targetState ?? $state->getName(), $states, RepoManager::getVersion());
	}
}