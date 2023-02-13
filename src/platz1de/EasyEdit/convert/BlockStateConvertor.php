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
	 * @var array<int, BlockStateTranslator>
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
			/** @var string $bedrockStringId */
			foreach ($jtb = RepoManager::getJson("java-to-bedrock", 10) as $javaState => $bedrockData) {
				self::$convertorsJTB[$javaState] = self::parseConvertor($bedrockData);
			}
			$rawJTB = json_encode($jtb, JSON_THROW_ON_ERROR);

			/** @var string $javaState */
			foreach ($btj = RepoManager::getJson("bedrock-to-java", 10) as $bedrockState => $javaData) {
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

	private static function parseConvertor(array $data): BlockStateTranslator
	{
		if (!isset($data["type"])) {
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
	 * @return BlockStateData
	 */
	public static function javaToBedrock(BlockStateData $state): BlockStateData
	{
		$converter = self::$convertorsJTB[$state->getName()] ?? null;
		if ($converter === null) {
			EditThread::getInstance()->debug("Unknown java state " . $state->getName());
			return $state;
		}
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
		return $converter->translate($state);
	}

	/**
	 * @param string $state
	 * @return int
	 */
	public static function javaStringToRuntime(string $state): int
	{
		$data = self::javaToBedrock(BlockParser::fromStateString($state, RepoManager::getVersion()));
		$data = GlobalBlockStateHandlers::getUpgrader()->getBlockStateUpgrader()->upgrade($data);
		return GlobalBlockStateHandlers::getDeserializer()->deserialize($data);
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
			$jtb = json_decode($rawJTB, true, 512, JSON_THROW_ON_ERROR);
			$btj = json_decode($rawBTJ, true, 512, JSON_THROW_ON_ERROR);
		} catch (Throwable $e) {
			EditThread::getInstance()->getLogger()->error("Failed to parse state data, Java state display is not available");
			EditThread::getInstance()->getLogger()->debug($e->getMessage());
			return;
		}

		foreach ($jtb as $javaState => $bedrockData) {
			self::$convertorsJTB[$javaState] = self::parseConvertor($bedrockData);
		}

		foreach ($btj as $bedrockState => $javaData) {
			self::$convertorsBTJ[$bedrockState] = self::parseConvertor($javaData);
		}
	}

	/**
	 * @return string[]
	 */
	public static function getAllKnownStates(): array
	{
		//TODO
		return self::$paletteTo;
	}

	/**
	 * @return bool
	 */
	public static function isAvailable(): bool
	{
		return self::$available;
	}
}