<?php

namespace platz1de\EasyEdit\thread\block;

use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\thread\ThreadData;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\data\bedrock\block\BlockStateSerializeException;
use pocketmine\data\bedrock\block\convert\UnsupportedBlockStateException;
use pocketmine\world\format\io\GlobalBlockStateHandlers;

/**
 * This class handles all block state translations needed by the EditThread
 * We can't expect plugins to properly register their block states to custom threads
 * So we just get them from the main thread (which is a bit slower, but only needed for a few tasks)
 */
class BlockStateTranslationManager
{
	private static ?BlockInputData $request;

	/**
	 * @param BlockStateData[] $states
	 * @param bool             $suppress Suppress invalid block state exceptions
	 * @return int[]|false
	 */
	public static function requestRuntimeId(array $states, bool $suppress): array|false
	{
		self::$request = null;
		EditThread::getInstance()->sendOutput(new BlockRequestData($states, true, $suppress));
		while (self::$request === null && ThreadData::canExecute() && EditThread::getInstance()->allowsExecution()) {
			EditThread::getInstance()->waitForData();
		}
		if (self::$request === null) {
			return false;
		}
		if (!self::$request->isRuntime()) {
			throw new BlockStateSerializeException("Expected runtime id, got block state");
		}
		/** @var int[] $res */
		$res = self::$request->getStates();
		self::$request = null;
		return $res;
	}

	/**
	 * @param int[] $ids
	 * @return BlockStateData[]|false
	 */
	public static function requestBlockState(array $ids): array|false
	{
		self::$request = null;
		EditThread::getInstance()->sendOutput(new BlockRequestData($ids, false));
		while (self::$request === null && ThreadData::canExecute() && EditThread::getInstance()->allowsExecution()) {
			EditThread::getInstance()->waitForData();
		}
		if (self::$request === null) {
			return false;
		}
		if (self::$request->isRuntime()) {
			throw new BlockStateSerializeException("Expected block state, got runtime id");
		}
		/** @var BlockStateData[] $res */
		$res = self::$request->getStates();
		self::$request = null;
		return $res;
	}

	public static function handleBlockResponse(BlockInputData $input): void
	{
		self::$request = $input;
	}

	private const MAX_PER_TICK = 250;
	private static bool $isRunning = false;
	/**
	 * @var BlockStateData[]|int[]
	 */
	private static array $missing;
	/**
	 * @var BlockStateData[]|int[]
	 */
	private static array $done;
	private static bool $toRuntime;
	private static bool $suppress;

	/**
	 * @param BlockStateData[]|int[] $states
	 * @param bool                   $type Whether to convert to runtime or block state
	 */
	public static function handleStateToRuntime(array $states, bool $type, bool $suppress): void
	{
		if (self::$isRunning) { //probably from a request before the thread crashed
			EasyEdit::getInstance()->getLogger()->warning("BlockStateTranslationManager is already running");
		}
		self::$toRuntime = $type;
		self::$suppress = $suppress;
		self::$missing = $states;
		self::$done = [];

		self::$isRunning = true;
	}

	public static function tick(): void
	{
		if (!self::$isRunning) {
			return;
		}

		$done = 0;
		foreach (self::$missing as $key => $state) {
			if ($done++ > self::MAX_PER_TICK) {
				break;
			}

			if (self::$toRuntime) {
				$state = GlobalBlockStateHandlers::getUpgrader()->getBlockStateUpgrader()->upgrade($state);
				try {
					self::$done[$key] = GlobalBlockStateHandlers::getDeserializer()->deserialize($state);
				} catch (UnsupportedBlockStateException $e) {
					if (!self::$suppress) {
						EditThread::getInstance()->debug($e->getMessage());
					}
					self::$done[$key] = GlobalBlockStateHandlers::getDeserializer()->deserialize(GlobalBlockStateHandlers::getUnknownBlockStateData());
				}
			} else {
				try {
					self::$done[$key] = GlobalBlockStateHandlers::getSerializer()->serialize($state);
				} catch (BlockStateSerializeException $e) {
					if (!self::$suppress) {
						EditThread::getInstance()->debug($e->getMessage());
					}
					self::$done[$key] = GlobalBlockStateHandlers::getUnknownBlockStateData();
				}
			}
			unset(self::$missing[$key]);
		}

		if (self::$missing === []) {
			BlockInputData::from(self::$done, self::$toRuntime);
			self::$done = [];
			self::$isRunning = false;
		}
	}
}