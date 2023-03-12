<?php

namespace platz1de\EasyEdit\thread\block;

use platz1de\EasyEdit\thread\input\InputData;
use platz1de\EasyEdit\utils\BlockParser;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\data\bedrock\block\BlockStateData;

class BlockInputData extends InputData
{
	/**
	 * @var BlockStateData[]|int[]
	 */
	private array $states;
	private bool $type;

	/**
	 * @param BlockStateData[]|int[] $states
	 * @param bool                   $type
	 */
	public static function from(array $states, bool $type): void
	{
		$data = new self();
		$data->states = $states;
		$data->type = $type;
		$data->send();
	}

	public function handle(): void
	{
		BlockStateTranslationManager::handleBlockResponse($this);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putBool($this->type);
		$stream->putInt(count($this->states));
		if ($this->type) {
			/** @var int $state */
			foreach ($this->states as $key => $state) {
				$stream->putInt($key);
				$stream->putInt($state);
			}
		} else {
			/** @var BlockStateData $state */
			foreach ($this->states as $key => $state) {
				$stream->putInt($key);
				$stream->putString(BlockParser::toStateString($state));
			}
		}
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->type = $stream->getBool();
		$states = [];
		for ($i = 0, $iMax = $stream->getInt(); $i < $iMax; $i++) {
			/** @noinspection AmbiguousMethodsCallsInArrayMappingInspection */
			$states[$stream->getInt()] = $this->type ? $stream->getInt() : BlockParser::fromStateString($stream->getString(), BlockStateData::CURRENT_VERSION);
		}
		$this->states = $states;
	}

	/**
	 * @return BlockStateData[]|int[]
	 */
	public function getStates(): array
	{
		return $this->states;
	}

	/**
	 * @return bool
	 */
	public function isRuntime(): bool
	{
		return $this->type;
	}
}