<?php

namespace platz1de\EasyEdit\convert;

use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\utils\BlockParser;
use platz1de\EasyEdit\utils\MixedUtils;
use platz1de\EasyEdit\utils\RepoManager;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\data\bedrock\block\BlockStateDeserializeException;
use pocketmine\nbt\tag\Tag;
use Throwable;

/**
 * Auto-completes bedrock block states
 * The pocketmine serializers are really strict about block states, so we need to make sure that they are valid
 */
class BedrockStatePreprocessor
{
	/**
	 * @var array<string, array<string, Tag>>
	 */
	private static array $defaults;
	private static bool $available = false;

	/**
	 * @internal cache before being passed to the main thread
	 * @var string
	 */
	public static string $rawData = "";

	public static function load(): void
	{
		self::$defaults = [];
		$rawData = "{}";

		try {
			/**
			 * @var string                $id
			 * @var array<string, string> $defaults
			 */
			foreach ($data = RepoManager::getJson("bedrock-defaults", 3) as $id => $defaults) {
				self::$defaults[$id] = array_map(static function (string $value): Tag {
					return BlockParser::tagFromStringValue($value);
				}, $defaults);
			}
			$rawData = json_encode($data, JSON_THROW_ON_ERROR);

			self::$available = true;
		} catch (Throwable $e) {
			EditThread::getInstance()->getLogger()->error("Failed to parse bedrock data, state parsing will be highly inaccurate");
			EditThread::getInstance()->getLogger()->debug($e->getMessage());
		}
		self::$rawData = $rawData;
	}

	/**
	 * @param BlockStateData $state
	 * @param bool           $strict
	 * @return BlockStateData
	 */
	public static function handle(BlockStateData $state, bool $strict = false): BlockStateData
	{
		if (!self::$available) {
			return $state;
		}
		if (!isset(self::$defaults[$state->getName()])) {
			return $state;
		}
		$states = $state->getStates();
		$defaults = self::$defaults[$state->getName()];
		foreach ($states as $name => $value) {
			if (!isset($defaults[$name])) {
				if ($strict) {
					throw new BlockStateDeserializeException("Unknown state \"$name\" for block " . $state->getName());
				}
				unset($states[$name]);
			}
		}
		foreach ($defaults as $name => $value) {
			if (!isset($states[$name])) {
				$states[$name] = clone $value;
			}
		}
		return new BlockStateData($state->getName(), $states, $state->getVersion());
	}

	public static function loadResourceData(string $rawData): void
	{
		try {
			$data = MixedUtils::decodeJson($rawData, 3);
		} catch (Throwable $e) {
			EditThread::getInstance()->getLogger()->error("Failed to parse bedrock data, state parsing will be highly inaccurate");
			EditThread::getInstance()->getLogger()->debug($e->getMessage());
			return;
		}

		/**
		 * @var string                $id
		 * @var array<string, string> $defaults
		 */
		foreach ($data as $id => $defaults) {
			self::$defaults[$id] = array_map(static function (string $value): Tag {
				return BlockParser::tagFromStringValue($value);
			}, $defaults);
		}

		self::$available = true;
	}

	/**
	 * @return bool
	 */
	public static function isAvailable(): bool
	{
		return self::$available;
	}
}