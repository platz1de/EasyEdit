<?php

namespace platz1de\EasyEdit\utils;

use InvalidArgumentException;
use platz1de\EasyEdit\convert\BedrockStatePreprocessor;
use platz1de\EasyEdit\convert\BlockStateConvertor;
use platz1de\EasyEdit\pattern\parser\ParseError;
use pocketmine\block\Block;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\data\bedrock\block\BlockStateDeserializeException;
use pocketmine\data\bedrock\block\convert\UnsupportedBlockStateException;
use pocketmine\item\LegacyStringToItemParser;
use pocketmine\item\LegacyStringToItemParserException;
use pocketmine\item\StringToItemParser;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\Tag;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use UnexpectedValueException;

class BlockParser
{
	/**
	 * @param string $string
	 * @return int
	 * @throws ParseError|UnsupportedBlockStateException
	 */
	public static function getRuntime(string $string): int
	{
		//Legacy Format (id:meta or name:meta)
		try {
			$item = LegacyStringToItemParser::getInstance()->parse(trim($string));
			return $item->getBlock()->getStateId();
		} catch (LegacyStringToItemParserException) {
			//Ignore
		}

		//Name Parser
		$item = StringToItemParser::getInstance()->parse(explode(":", str_replace([" ", "minecraft:"], ["_", ""], trim($string)))[0]);
		if ($item !== null) {
			return $item->getBlock()->getStateId();
		}

		$string = "minecraft:" . str_replace([" ", "minecraft:"], ["_", ""], trim($string));
		try {
			$state = self::fromStateString($string, BlockStateData::CURRENT_VERSION);
		} catch (InvalidArgumentException $e) {
			throw new UnsupportedBlockStateException($e->getMessage());
		}

		$ex = null;
		try {
			return GlobalBlockStateHandlers::getDeserializer()->deserialize(BedrockStatePreprocessor::handle($state));
		} catch (UnsupportedBlockStateException) {
			//Ignore
		} catch (BlockStateDeserializeException $e) {
			$ex = $e;
		}

		if (!BlockStateConvertor::isAvailable()) {
			if ($ex !== null) {
				throw new ParseError("Failed to parse block: " . $string . PHP_EOL . "Bedrock parser: " . $ex->getMessage());
			}
			throw new UnsupportedBlockStateException("Failed to parse block " . $string . ": Unknown block");
		}

		$state = new BlockStateData($state->getName(), $state->getStates(), RepoManager::getVersion());
		try {
			$state = BlockStateConvertor::javaToBedrock($state, true);
		} catch (UnsupportedBlockStateException) {
			if ($ex !== null) {
				throw new ParseError("Failed to parse block: " . $string . PHP_EOL . "Bedrock parser: " . $ex->getMessage());
			}
			throw new UnsupportedBlockStateException("Failed to parse block " . $string . ": Unknown block");
		} catch (UnexpectedValueException $e) {
			if ($ex !== null) {
				throw new ParseError("Failed to parse block: " . $string . PHP_EOL . "Bedrock parser: " . $e->getMessage() . PHP_EOL . "Java parser: " . $ex->getMessage());
			}
			throw new ParseError("Failed to parse block: " . $string . PHP_EOL . "Bedrock parser: " . $e->getMessage());
		}
		$state = GlobalBlockStateHandlers::getUpgrader()->getBlockStateUpgrader()->upgrade($state);
		return GlobalBlockStateHandlers::getDeserializer()->deserialize($state);
	}

	/**
	 * @param int $block
	 * @return string
	 */
	public static function runtimeToStateString(int $block): string
	{
		return self::toStateString(GlobalBlockStateHandlers::getSerializer()->serialize($block));
	}

	/**
	 * @param Block $block
	 * @return string
	 */
	public static function blockToStateString(Block $block): string
	{
		return self::toStateString(GlobalBlockStateHandlers::getSerializer()->serializeBlock($block));
	}

	/**
	 * @param BlockStateData $block
	 * @return string id[stateName=value,stateName=value...]
	 */
	public static function toStateString(BlockStateData $block): string
	{
		if ($block->getStates() === []) {
			return $block->getName();
		}
		$states = [];
		foreach ($block->getStates() as $key => $value) {
			$states[] = $key . "=" . self::tagToStringValue($value);
		}
		return $block->getName() . "[" . implode(",", $states) . "]";
	}

	/**
	 * @param string $block
	 * @param int    $version
	 * @return BlockStateData
	 */
	public static function fromStateString(string $block, int $version): BlockStateData
	{
		if (preg_match("/^([a-z\d_:]+)(?:\[((?:[a-z\d_=:,]*|(?&r))+)])?$(?(DEFINE)(?<r>\[(?:[a-z\d_=,:]*|(?R))+]))/", strtolower($block), $matches) === 1) {
			$block = $matches[1];
			if (!isset($matches[2]) || $matches[2] === "") {
				return new BlockStateData($block, [], $version);
			}
			$states = [];
			preg_match_all("/([^,\[]+|(?&r))+(?(DEFINE)(?<r>\[(?:[a-z\d_=,:]*|(?1))+]))/", $matches[2], $statesData);
			foreach ($statesData[0] as $state) {
				preg_match_all("/([^=\[]+|(?&r))+(?(DEFINE)(?<r>\[(?:[a-z\d_=,:]*|(?1))+]))/", $state, $stateData);
				if (count($stateData[0]) === 2) {
					$states[(string) $stateData[0][0]] = self::tagFromStringValue($stateData[0][1]);
				} else {
					throw new InvalidArgumentException("Invalid state argument " . $block);
				}
			}
			return new BlockStateData($block, $states, $version);
		}
		throw new InvalidArgumentException("Invalid block state string " . $block);
	}

	/**
	 * @param Tag $tag
	 * @return string
	 */
	public static function tagToStringValue(Tag $tag): string
	{
		if ($tag instanceof IntTag) {
			return (string) $tag->getValue();
		}
		if ($tag instanceof ByteTag) {
			return $tag->getValue() === 1 ? "true" : "false";
		}
		if ($tag instanceof StringTag) {
			return $tag->getValue();
		}
		throw new InvalidArgumentException("Unexpected tag type " . get_class($tag));
	}

	/**
	 * @param string $block
	 * @return Tag
	 */
	public static function tagFromStringValue(string $block): Tag
	{
		return match ($block) {
			"true" => new ByteTag(1),
			"false" => new ByteTag(0),
			default => is_numeric($block) ? new IntTag((int) $block) : new StringTag($block)
		};
	}

	private static int $invalidBlockId;

	public static function getInvalidBlockId(): int
	{
		return self::$invalidBlockId ??= GlobalBlockStateHandlers::getDeserializer()->deserialize(GlobalBlockStateHandlers::getUnknownBlockStateData());
	}
}