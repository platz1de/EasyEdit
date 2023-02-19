<?php

namespace platz1de\EasyEdit\utils;

use InvalidArgumentException;
use platz1de\EasyEdit\pattern\parser\ParseError;
use pocketmine\block\Block;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\item\LegacyStringToItemParser;
use pocketmine\item\LegacyStringToItemParserException;
use pocketmine\item\StringToItemParser;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\Tag;
use pocketmine\world\format\io\GlobalBlockStateHandlers;

class BlockParser
{
	/**
	 * @param string $string
	 * @return int
	 * @throws ParseError
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
		//Block State Parser (bedrock)
		try {
			return self::runtimeFromStateString($string, BlockStateData::CURRENT_VERSION);
		} catch (InvalidArgumentException) {
			//Ignore
		}

		//TODO: Block State Parser (java)
		throw new ParseError("Unknown Block " . $string);
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
	 * @return int
	 */
	public static function runtimeFromStateString(string $block, int $version): int
	{
		$state = self::fromStateString($block, $version);
		$state = GlobalBlockStateHandlers::getUpgrader()->getBlockStateUpgrader()->upgrade($state);
		return GlobalBlockStateHandlers::getDeserializer()->deserialize($state);
	}

	/**
	 * @param string $block
	 * @param int    $version
	 * @return Block
	 */
	public static function blockFromStateString(string $block, int $version): Block
	{
		$state = self::fromStateString($block, $version);
		$state = GlobalBlockStateHandlers::getUpgrader()->getBlockStateUpgrader()->upgrade($state);
		return GlobalBlockStateHandlers::getDeserializer()->deserializeBlock($state);
	}

	/**
	 * @param string $block
	 * @param int    $version
	 * @return BlockStateData
	 */
	public static function fromStateString(string $block, int $version): BlockStateData
	{
		if (preg_match("/^([a-z\d_:]+)(?:\[([a-z\d_=,]*)])?$/", strtolower($block), $matches) === 1) {
			$block = $matches[1];
			if (!isset($matches[2])) {
				return new BlockStateData($block, [], $version);
			}
			$states = [];
			foreach (explode(",", $matches[2]) as $state) {
				$state = explode("=", $state);
				if (count($state) === 2) {
					$states[$state[0]] = self::tagFromStringValue($state[1]);
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
}