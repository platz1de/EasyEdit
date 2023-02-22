<?php

namespace platz1de\EasyEdit\convert;

use platz1de\EasyEdit\convert\block\BlockStateTranslator;
use platz1de\EasyEdit\convert\block\CombinedMultiStateTranslator;
use platz1de\EasyEdit\convert\block\CombinedStateTranslator;
use platz1de\EasyEdit\convert\block\MultiStateTranslator;
use platz1de\EasyEdit\convert\block\ReplicaStateTranslator;
use platz1de\EasyEdit\convert\block\SingularStateTranslator;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\thread\output\ResourceData;
use platz1de\EasyEdit\utils\BlockParser;
use platz1de\EasyEdit\utils\MixedUtils;
use platz1de\EasyEdit\utils\RepoManager;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use Throwable;
use UnexpectedValueException;

/**
 * Convertor between java block states and bedrocks current ids
 */
class BlockStateConvertor
{
	/**
	 * @var array<string, BlockStateTranslator>
	 */
	private static array $convertorsJTB;
	/**
	 * @var array<string, BlockStateTranslator>
	 */
	private static array $convertorsBTJ;
	private static bool $available = false;

	public static function load(): void
	{
		self::$convertorsJTB = [];
		self::$convertorsBTJ = [];
		$rawJTB = "{}";
		$rawBTJ = "{}";

		try {
			foreach ($jtb = RepoManager::getJson("java-to-bedrock", 10) as $javaState => $bedrockData) {
				if (!is_array($bedrockData)) {
					throw new UnexpectedValueException("Invalid bedrock data for $javaState");
				}
				self::$convertorsJTB[$javaState] = self::parseConvertor($bedrockData);
			}
			$rawJTB = json_encode($jtb, JSON_THROW_ON_ERROR);

			foreach ($btj = RepoManager::getJson("bedrock-to-java", 10) as $bedrockState => $javaData) {
				if (!is_array($javaData)) {
					throw new UnexpectedValueException("Invalid java data for $bedrockState");
				}
				self::$convertorsBTJ[$bedrockState] = self::parseConvertor($javaData);
			}
			$rawBTJ = json_encode($btj, JSON_THROW_ON_ERROR);

			self::$available = true;
		} catch (Throwable $e) {
			EditThread::getInstance()->getLogger()->error("Failed to parse state data, Sponge schematic conversion is not available");
			EditThread::getInstance()->getLogger()->debug($e->getMessage());
		}

		EditThread::getInstance()->sendOutput(new ResourceData($rawJTB, $rawBTJ));
	}

	/**
	 * @param array<string, mixed> $data
	 * @return BlockStateTranslator
	 */
	private static function parseConvertor(array $data): BlockStateTranslator
	{
		if (!isset($data["type"]) || !is_string($data["type"])) {
			throw new UnexpectedValueException("Missing type in convertor");
		}
		$type = $data["type"];
		return match ($type) {
			"none" => new ReplicaStateTranslator($data),
			"singular" => new SingularStateTranslator($data),
			"multi" => new MultiStateTranslator($data),
			"combined" => new CombinedStateTranslator($data),
			"combined_multi" => new CombinedMultiStateTranslator($data),
			default => throw new UnexpectedValueException("Unknown convertor type $type")
		};
	}

	/**
	 * @param BlockStateData $state
	 * @param bool           $strict
	 * @return BlockStateData
	 */
	public static function javaToBedrock(BlockStateData $state, bool $strict = false): BlockStateData
	{
		$converter = self::$convertorsJTB[$state->getName()] ?? null;
		if ($converter === null) {
			if ($strict) {
				throw new UnexpectedValueException("Unknown java state " . $state->getName());
			}
			EditThread::getInstance()->debug("Unknown java state " . $state->getName());
			return $state;
		}
		$state = $converter->applyDefaults($state);
		return $converter->translate($state);
	}

	/**
	 * @param BlockStateData $state
	 * @return BlockStateData
	 */
	public static function bedrockToJava(BlockStateData $state): BlockStateData
	{
		$converter = self::$convertorsBTJ[$state->getName()] ?? null;
		if ($converter === null) {
			EditThread::getInstance()->debug("Unknown bedrock state " . $state->getName());
			return $state;
		}
		$state = $converter->translate($state);
		return $converter->applyDefaults($state);
	}

	/**
	 * @param string $state
	 * @return int
	 */
	public static function javaStringToRuntime(string $state): int
	{
		try {
			$data = self::javaToBedrock(BlockParser::fromStateString($state, RepoManager::getVersion()));
			$data = GlobalBlockStateHandlers::getUpgrader()->getBlockStateUpgrader()->upgrade($data);
			return GlobalBlockStateHandlers::getDeserializer()->deserialize($data);
		} catch (Throwable $e) {
			EditThread::getInstance()->debug("Failed to parse java state $state");
			EditThread::getInstance()->debug($e->getMessage());
			return GlobalBlockStateHandlers::getDeserializer()->deserialize(GlobalBlockStateHandlers::getUnknownBlockStateData());
		}
	}

	/**
	 * @param int $state
	 * @return string
	 */
	public static function runtimeToJavaString(int $state): string
	{
		return BlockParser::toStateString(self::bedrockToJava(GlobalBlockStateHandlers::getSerializer()->serialize($state)));
	}

	public static function loadResourceData(string $rawJTB, string $rawBTJ): void
	{
		try {
			$jtb = MixedUtils::decodeJson($rawJTB, 10);
			$btj = MixedUtils::decodeJson($rawBTJ, 10);
		} catch (Throwable $e) {
			EditThread::getInstance()->getLogger()->error("Failed to parse state data, Java state display is not available");
			EditThread::getInstance()->getLogger()->debug($e->getMessage());
			return;
		}

		foreach ($jtb as $javaState => $bedrockData) {
			if (!is_array($bedrockData)) {
				throw new UnexpectedValueException("Invalid bedrock data for $javaState");
			}
			self::$convertorsJTB[$javaState] = self::parseConvertor($bedrockData);
		}

		foreach ($btj as $bedrockState => $javaData) {
			if (!is_array($javaData)) {
				throw new UnexpectedValueException("Invalid java data for $bedrockState");
			}
			self::$convertorsBTJ[$bedrockState] = self::parseConvertor($javaData);
		}
	}

	/**
	 * @return int[]
	 */
	public static function getAllKnownStates(): array
	{
		$states = [];
		foreach (self::$convertorsJTB as $state => $converter) {
			foreach ($converter->getAllPossibleStates($state) as $possibleState) {
				$states[] = $possibleState;
			}
		}
		return $states;
	}

	/**
	 * @return bool
	 */
	public static function isAvailable(): bool
	{
		return self::$available;
	}
}