<?php

namespace platz1de\EasyEdit\thread\block;

use platz1de\EasyEdit\thread\output\OutputData;
use platz1de\EasyEdit\utils\BlockParser;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\data\bedrock\block\BlockStateData;

class BlockRequestData extends OutputData
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
	public function __construct(array $states, bool $type)
	{
		$this->states = $states;
		$this->type = $type;
	}

	public function handle(): void
	{
		BlockStateTranslationManager::handleStateToRuntime($this->states, $this->type);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putBool($this->type);
		$stream->putInt(count($this->states));
		if ($this->type) {
			foreach ($this->states as $key => $state) {
				$stream->putInt($key);
				$stream->putString(BlockParser::toStateString($state));
			}
		} else {
			foreach ($this->states as $key => $state) {
				$stream->putInt($key);
				$stream->putInt($state);
			}
		}
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->type = $stream->getBool();
		$states = [];
		for ($i = 0, $iMax = $stream->getInt(); $i < $iMax; $i++) {
			$states[$stream->getInt()] = $this->type ? BlockParser::fromStateString($stream->getString(), BlockStateData::CURRENT_VERSION) : $stream->getInt();
		}
		$this->states = $states;
	}
}