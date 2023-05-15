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
class SimpleStateTranslator extends BlockStateTranslator
{
	/**
	 * @var string
	 */
	protected ?string $targetState = null;
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
	 * @var array<string, Tag>
	 */
	private array $extraTileData = [];
	/**
	 * @var string[]
	 */
	private array $removedTileData = [];

	/**
	 * @param array<string, mixed> $data
	 */
	public function __construct(array $data)
	{
		parent::__construct($data);

		if (isset($data["name"])) {
			if (!is_string($data["name"])) {
				throw new UnexpectedValueException("Missing name");
			}
			$this->targetState = $data["name"];
		}

		$added = $data["additions"] ?? [];
		if (!is_array($added)) {
			throw new UnexpectedValueException("additions must be an array");
		}
		foreach ($added as $state => $value) {
			$this->addedStates[$state] = BlockParser::tagFromStringValue($value);
		}

		$removed = $data["removals"] ?? [];
		if (!is_array($removed)) {
			throw new UnexpectedValueException("removals must be an array");
		}
		$this->removedStates = $removed;

		$replace = $data["remaps"] ?? [];
		if (!is_array($replace)) {
			throw new UnexpectedValueException("remaps must be an array");
		}
		foreach ($replace as $stateName => $values) {
			$this->valueReplacements[$stateName] = [];
			foreach ($values as $key => $value) {
				$this->valueReplacements[$stateName][(string) $key] = BlockParser::tagFromStringValue($value);
			}
		}

		$renames = $data["renames"] ?? [];
		if (!is_array($renames)) {
			throw new UnexpectedValueException("renames must be an array");
		}
		foreach ($renames as $old => $new) {
			if (!is_string($old) || !is_string($new)) {
				throw new UnexpectedValueException("renames must be an array of strings");
			}
			$this->renamedStates[$old] = $new;
		}

		if (isset($data["tile_extra"])) {
			if (!is_array($data["tile_extra"])) {
				throw new UnexpectedValueException("tile_extra must be an array");
			}
			if (array_keys($data["tile_extra"]) === range(0, count($data["tile_extra"]) - 1)) {
				$this->removedTileData = $data["tile_extra"];
			} else {
				foreach ($data["tile_extra"] as $key => $value) {
					$this->extraTileData[$key] = BlockParser::tagFromStringValue($value);
				}
			}
		}
	}

	public function translate(BlockStateData $state): BlockStateData
	{
		$state = $this->applyDefaultTileData($state);
		return new BlockStateData($this->targetState ?? $state->getName(), $this->process($state->getStates()), RepoManager::getVersion());
	}

	protected function applyDefaultTileData(BlockStateData $data): BlockStateData
	{
		if ($this->extraTileData === []) {
			return $data;
		}
		$states = $data->getStates();
		foreach ($this->extraTileData as $state => $value) {
			if (!isset($states[$state])) {
				$states[$state] = clone $value;
			}
		}
		return new BlockStateData($data->getName(), $states, $data->getVersion());
	}

	/**
	 * @param array<string, Tag> $states
	 * @return array<string, Tag>
	 */
	protected function process(array $states, SimpleStateTranslator $allowedOverwrites = null): array
	{
		foreach ($this->removedStates as $state) {
			if (!isset($states[$state])) {
				throw new UnexpectedValueException("State $state to remove does not exist");
			}
			unset($states[$state]);
		}

		foreach ($this->renamedStates as $old => $new) {
			if (!isset($states[$old])) {
				throw new UnexpectedValueException("State $old to rename does not exist");
			}
			if (isset($states[$new])) {
				throw new UnexpectedValueException("State $new already exists");
			}
			$states[$new] = $states[$old];
			unset($states[$old]);
		}

		foreach ($this->valueReplacements as $state => $remap) {
			if (!isset($states[$state])) {
				throw new UnexpectedValueException("State $state to remap does not exist");
			}
			$stateValue = BlockParser::tagToStringValue($states[$state]);
			if (!isset($remap[$stateValue])) {
				throw new UnexpectedValueException("State $state has value $stateValue, but no remap for it");
			}
			$states[$state] = clone $remap[$stateValue];
		}

		foreach ($this->addedStates as $state => $value) {
			if (isset($states[$state]) && ($allowedOverwrites === null || !isset($allowedOverwrites->addedStates[$state]))) {
				throw new UnexpectedValueException("State $state already exists");
			}
			$states[$state] = clone $value;
		}

		return $states;
	}

	public function removeTileData(BlockStateData $state): BlockStateData
	{
		if ($this->removedTileData === []) {
			return $state;
		}
		$states = $state->getStates();
		foreach ($this->removedTileData as $stateName) {
			if (!isset($states[$stateName])) {
				throw new UnexpectedValueException("State $stateName to remove does not exist");
			}
			unset($states[$stateName]);
		}
		return new BlockStateData($state->getName(), $states, $state->getVersion());
	}
}