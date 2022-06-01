<?php

namespace platz1de\EasyEdit\thread\input;

use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;

/**
 * Transfers RuntimeBlockMapping to the thread, only contains values set in enable or loading of plugins
 */
class RuntimeInputData extends InputData
{
	public static function create(): void
	{
		$data = new self();
		$data->send();
	}

	public function handle(): void
	{
		//NOOP
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 * @noinspection PhpUndefinedFieldInspection
	 */
	public function putData(ExtendedBinaryStream $stream): void
	{
		(function () use ($stream) {
			$stream->putInt(count($this->legacyToRuntimeMap));
			foreach ($this->legacyToRuntimeMap as $legacy => $runtime) {
				$stream->putInt($legacy);
				$stream->putInt($runtime);
			}
			$stream->putInt(count($this->runtimeToLegacyMap));
			foreach ($this->runtimeToLegacyMap as $runtime => $legacy) {
				$stream->putInt($runtime);
				$stream->putInt($legacy);
			}
		})->call(RuntimeBlockMapping::getInstance());
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 * @noinspection PhpUndefinedFieldInspection
	 * @noinspection AmbiguousMethodsCallsInArrayMappingInspection
	 */
	public function parseData(ExtendedBinaryStream $stream): void
	{
		(function () use ($stream) {
			$count = $stream->getInt();
			for ($i = 0; $i < $count; $i++) {
				$this->legacyToRuntimeMap[$stream->getInt()] = $stream->getInt();
			}
			$count = $stream->getInt();
			for ($i = 0; $i < $count; $i++) {
				$this->runtimeToLegacyMap[$stream->getInt()] = $stream->getInt();
			}
		})->call(RuntimeBlockMapping::getInstance());
	}
}