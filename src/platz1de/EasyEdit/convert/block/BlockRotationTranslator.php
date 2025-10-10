<?php

namespace platz1de\EasyEdit\convert\block;

use platz1de\EasyEdit\utils\BlockParser;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\nbt\tag\Tag;
use UnexpectedValueException;

class BlockRotationTranslator
{
	/**
	 * @var array{rename: array<string, string>, remap: array<string, array<string, Tag>>}
	 */
	private array $rotationStates = ["remap" => [], "rename" => []];
	/**
	 * @var array{rename: array<string, string>, remap: array<string, array<string, Tag>>}
	 */
	private array $flipXStates = ["remap" => [], "rename" => []];
	/**
	 * @var array{rename: array<string, string>, remap: array<string, array<string, Tag>>}
	 */
	private array $flipYStates = ["remap" => [], "rename" => []];
	/**
	 * @var array{rename: array<string, string>, remap: array<string, array<string, Tag>>}
	 */
	private array $flipZStates = ["remap" => [], "rename" => []];

	/**
	 * @param array<string, mixed> $data
	 */
	public function __construct(array $data)
	{
		$this->parseMapping($data, "rotate", $this->rotationStates);
		$this->parseMapping($data, "flip-x", $this->flipXStates);
		$this->parseMapping($data, "flip-y", $this->flipYStates);
		$this->parseMapping($data, "flip-z", $this->flipZStates);
	}

	/**
	 * @param array<string, mixed>                                                           $data
	 * @param string                                                                         $key
	 * @param array{rename: array<string, string>, remap: array<string, array<string, Tag>>} $target
	 * @return void
	 */
	private function parseMapping(array $data, string $key, array &$target): void
	{
		if (isset($data[$key])) {
			$rotate = $data[$key];
			if (!is_array($rotate)) {
				throw new UnexpectedValueException("$key must be an array");
			}
			foreach ($rotate as $state => $map) {
				if (!is_array($map)) {
					throw new UnexpectedValueException("$key map must be an array");
				}
				foreach ($map as $value => $newValue) {
					if (!is_string($newValue)) {
						throw new UnexpectedValueException("$key map value must be a string");
					}
					if ($state === "__rename") {
						$target["rename"][$value] = $newValue;
					} else {
						$target["remap"][$state][$value] = BlockParser::tagFromStringValue($newValue);
					}
				}
			}
		}
	}

	public function rotate(BlockStateData $state): BlockStateData
	{
		return $this->translate($state, $this->rotationStates);
	}

	public function flipX(BlockStateData $state): BlockStateData
	{
		return $this->translate($state, $this->flipXStates);
	}

	public function flipY(BlockStateData $state): BlockStateData
	{
		return $this->translate($state, $this->flipYStates);
	}

	public function flipZ(BlockStateData $state): BlockStateData
	{
		return $this->translate($state, $this->flipZStates);
	}

	/**
	 * @param BlockStateData                                                                 $state
	 * @param array{rename: array<string, string>, remap: array<string, array<string, Tag>>} $mapping
	 * @return BlockStateData
	 */
	private function translate(BlockStateData $state, array $mapping): BlockStateData
	{
		$states = $state->getStates();
		foreach ($mapping["remap"] as $stateName => $stateMap) {
			if (isset($states[$stateName])) {
				$states[$stateName] = $stateMap[BlockParser::tagToStringValue($states[$stateName])] ?? $states[$stateName];
			}
		}
		$cache = $states;
		foreach ($mapping["rename"] as $oldName => $newName) {
			if (isset($cache[$oldName])) {
				$states[$newName] = $cache[$oldName];
			}
		}
		return new BlockStateData($state->getName(), $states, $state->getVersion());
	}
}