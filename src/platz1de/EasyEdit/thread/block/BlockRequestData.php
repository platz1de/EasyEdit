<?php

namespace platz1de\EasyEdit\thread\block;

use platz1de\EasyEdit\thread\output\OutputData;
use platz1de\EasyEdit\utils\BlockParser;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\data\bedrock\block\BlockStateData;

class BlockRequestData extends OutputData
{
	/**
	 * @param BlockStateData[]|int[] $states
	 * @param bool                   $type
	 * @param bool                   $suppress
	 * @param bool                   $full
	 */
	public function __construct(private array $states, private bool $type, private bool $suppress = false, private bool $full = false) {}

	public function handle(): void
	{
		BlockStateTranslationManager::handleStateToRuntime($this->states, $this->type, $this->suppress, $this->full);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putBool($this->type);
		$stream->putInt(count($this->states));
		if ($this->type) {
			/** @var BlockStateData $state */
			foreach ($this->states as $key => $state) {
				$stream->putInt($key);
				$stream->putString(BlockParser::toStateString($state));
			}
		} else {
			/** @var int $state */
			foreach ($this->states as $key => $state) {
				$stream->putInt($key);
				$stream->putInt($state);
			}
		}
		$stream->putBool($this->suppress);
		$stream->putBool($this->full);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->type = $stream->getBool();
		$states = [];
		for ($i = 0, $iMax = $stream->getInt(); $i < $iMax; $i++) {
			$states[$stream->getInt()] = $this->type ? BlockParser::fromStateString($stream->getString(), BlockStateData::CURRENT_VERSION) : $stream->getInt();
		}
		$this->states = $states;
		$this->suppress = $stream->getBool();
		$this->full = $stream->getBool();
	}
}