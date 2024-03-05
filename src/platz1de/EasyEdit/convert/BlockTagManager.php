<?php

namespace platz1de\EasyEdit\convert;

use platz1de\EasyEdit\pattern\block\BlockGroup;
use platz1de\EasyEdit\pattern\block\MaskedBlockGroup;
use platz1de\EasyEdit\thread\block\BlockStateTranslationManager;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\utils\BlockParser;
use platz1de\EasyEdit\utils\MixedUtils;
use platz1de\EasyEdit\utils\RepoManager;
use pocketmine\block\Block;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\data\bedrock\block\convert\UnsupportedBlockStateException;
use Throwable;
use UnexpectedValueException;

/**
 * Manages known block tags
 */
class BlockTagManager
{
	/**
	 * @var array<string, int[]>
	 */
	private static array $tags;
	/**
	 * @var array<string, int[]>
	 */
	private static array $tagStates;
	private static bool $available = false;

	/**
	 * @internal cache before being passed to the main thread
	 * @var string
	 */
	public static string $rawData = "";

	public static function load(): void
	{
		self::$tags = [];
		$rawData = "{}";

		if (!BedrockStatePreprocessor::isAvailable()) {
			self::$rawData = $rawData;
			EditThread::getInstance()->getLogger()->error("Failed to parse block tag data, block tags are not available");
			return;
		}

		try {
			$version = RepoManager::getVersion();
			$tagMap = [];
			$tagRelations = [];
			foreach (RepoManager::getJson("block-tags", 3) as $tag => $data) {
				if (!is_array($data)) {
					throw new UnexpectedValueException("Invalid data for $tag");
				}
				foreach ($data as $block) {
					$tagMap[] = BedrockStatePreprocessor::handle(BlockParser::fromStateString($block, $version));
				}
				$tagRelations[$tag] = count($tagMap) - 1;
			}

			$tagMap = BlockStateTranslationManager::requestRuntimeId($tagMap, true);
			$i = 0;
			foreach ($tagRelations as $tag => $relations) {
				$tagIds = [];
				for ($j = $i; $j <= $relations; ++$j) {
					$tagIds[] = $tagMap[$j] >> Block::INTERNAL_STATE_DATA_BITS;
				}
				$i = $relations + 1;
				self::$tags[$tag] = $tagIds;
			}

			$rawData = json_encode(self::$tags, JSON_THROW_ON_ERROR);

			self::$available = true;
		} catch (Throwable $e) {
			EditThread::getInstance()->getLogger()->error("Failed to parse block tag data, block tags are not available");
			EditThread::getInstance()->getLogger()->debug($e->getMessage());
		}
		self::$rawData = $rawData;
	}

	/**
	 * @param string $tag
	 * @param bool   $isMask Non-mask tags are only available on the main thread
	 * @return BlockGroup|MaskedBlockGroup
	 */
	public static function getTag(string $tag, bool $isMask): BlockGroup|MaskedBlockGroup
	{
		if (!isset(self::$tags[$tag])) {
			throw new UnsupportedBlockStateException("Unknown block tag $tag");
		}
		if ($isMask) {
			return new MaskedBlockGroup(self::$tags[$tag]);
		}
		$group = [];
		foreach (self::$tagStates[$tag] as $id) {
			if ($id !== BlockParser::getInvalidBlockId()) {
				$group[] = $id;
			}
		}
		if ($group === []) {
			throw new UnsupportedBlockStateException("No blocks available for tag $tag");
		}
		return new BlockGroup($group);
	}

	public static function loadResourceData(string $rawData): void
	{
		try {
			/** @var array<string, int[]> $data */
			$data = MixedUtils::decodeJson($rawData, 3);
		} catch (Throwable $e) {
			EditThread::getInstance()->getLogger()->error("Failed to parse block tag data, block tags are not available");
			EditThread::getInstance()->getLogger()->debug($e->getMessage());
			return;
		}

		self::$tags = $data;

		//TODO: just pass the whole states over instead of using hacky ways to translate type id to blocks
		/** @var Block[] $blocks */
		$blocks = (function () {
			/** @noinspection all */
			return $this->typeIndex;
		})->call(RuntimeBlockStateRegistry::getInstance());
		self::$tagStates = [];
		foreach ($data as $tag => $ids) {
			$states = [];
			foreach ($ids as $id) {
				foreach ($blocks[$id]->generateStatePermutations() as $state) {
					$states[] = $state->getStateId();
				}
			}
			self::$tagStates[$tag] = $states;
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