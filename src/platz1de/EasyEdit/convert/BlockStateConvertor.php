<?php

namespace platz1de\EasyEdit\convert;

use platz1de\EasyEdit\convert\block\BlockStateTranslator;
use platz1de\EasyEdit\convert\block\MultiStateTranslator;
use platz1de\EasyEdit\convert\block\SimpleStateTranslator;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\thread\output\ResourceData;
use platz1de\EasyEdit\utils\MixedUtils;
use platz1de\EasyEdit\utils\RepoManager;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\data\bedrock\block\convert\UnsupportedBlockStateException;
use pocketmine\nbt\tag\CompoundTag;
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
			foreach ($jtb = RepoManager::getJson("java-to-bedrock", 15) as $javaState => $bedrockData) {
				if (!is_array($bedrockData)) {
					throw new UnexpectedValueException("Invalid bedrock data for $javaState");
				}
				self::$convertorsJTB[$javaState] = self::parseConvertor($bedrockData);
			}
			$rawJTB = json_encode($jtb, JSON_THROW_ON_ERROR);

			foreach ($btj = RepoManager::getJson("bedrock-to-java", 15) as $bedrockState => $javaData) {
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
		if (isset($data["identifier"])) {
			return new MultiStateTranslator($data);
		}

		return new SimpleStateTranslator($data);
	}

	/**
	 * @param BlockStateData   $state
	 * @param bool             $strict
	 * @param CompoundTag|null $compound
	 * @return BlockStateData
	 */
	public static function javaToBedrock(BlockStateData $state, bool $strict = false, ?CompoundTag &$compound = null): BlockStateData
	{
		$converter = self::$convertorsJTB[$state->getName()] ?? null;
		if ($converter === null) {
			if ($strict) {
				throw new UnsupportedBlockStateException("Unknown java state " . $state->getName());
			}
			EditThread::getInstance()->debug("Unknown java state " . $state->getName());
			return $state;
		}
		$state = $converter->applyDefaults($state);
		try {
			$res = $converter->translate($state);
			$compound = TileConvertor::preprocessTileState($res);
			return $converter->removeTileData($res);
		} catch (Throwable $e) {
			if ($strict) {
				throw new UnexpectedValueException($e->getMessage(), 0, $e);
			}
			EditThread::getInstance()->getLogger()->critical("Failed to convert " . $state->getName() . " to bedrock");
			EditThread::getInstance()->getLogger()->logException($e);
			return $state;
		}
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
		try {
			$state = $converter->translate($state);
		} catch (Throwable $e) {
			EditThread::getInstance()->getLogger()->critical("Failed to convert " . $state->getName() . " to java");
			EditThread::getInstance()->getLogger()->logException($e);
		}
		return $converter->applyDefaults($state);
	}

	public static function loadResourceData(string $rawJTB, string $rawBTJ): void
	{
		try {
			$jtb = MixedUtils::decodeJson($rawJTB, 15);
			$btj = MixedUtils::decodeJson($rawBTJ, 15);
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