<?php

namespace platz1de\EasyEdit\thread\block;

use InvalidArgumentException;
use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\task\CancelException;
use platz1de\EasyEdit\thread\EditThread;
use pocketmine\block\RuntimeBlockStateRegistry;
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
	 * @param bool             $full     Return full block state data
	 * @return int[]
	 * @throws CancelException
	 */
	public static function requestRuntimeId(array $states, bool $suppress = false, bool $full = false): array
	{
		self::$request = null;
		EditThread::getInstance()->sendOutput(new BlockRequestData($states, true, $suppress, $full));
		while (self::waitingForResponse()) {
			EditThread::getInstance()->waitForData();
		}
		/** @var BlockInputData $request */
		$request = self::$request;
		if (!$request->isRuntime()) {
			throw new BlockStateSerializeException("Expected runtime id, got block state");
		}
		/** @var int[] $res */
		$res = $request->getStates();
		self::$request = null;
		return $res;
	}

	/**
	 * @param int[] $ids
	 * @return BlockStateData[]
	 * @throws CancelException
	 */
	public static function requestBlockState(array $ids): array
	{
		self::$request = null;
		EditThread::getInstance()->sendOutput(new BlockRequestData($ids, false));
		while (self::waitingForResponse()) {
			EditThread::getInstance()->waitForData();
		}
		/** @var BlockInputData $request */
		$request = self::$request;
		if ($request->isRuntime()) {
			throw new BlockStateSerializeException("Expected block state, got runtime id");
		}
		/** @var BlockStateData[] $res */
		$res = $request->getStates();
		self::$request = null;
		return $res;
	}

	public static function handleBlockResponse(BlockInputData $input): void
	{
		self::$request = $input;
	}

	/**
	 * Thanks for being stupid phpstan
	 */
	private static function waitingForResponse(): bool
	{
		return self::$request === null;
	}

	private const MAX_PER_TICK = 250;
	private static bool $isRunning = false;
	/**
	 * @var BlockStateData[]|int[]
	 */
	private static ?array $missing;
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
	public static function handleStateToRuntime(array $states, bool $type, bool $suppress, bool $full): void
	{
		if (self::$isRunning) { //probably from a request before the thread crashed
			EasyEdit::getInstance()->getLogger()->warning("BlockStateTranslationManager is already running");
		}
		if ($full && $states !== []) {
			throw new InvalidArgumentException("Full block state data can only be requested for empty states");
		}
		self::$toRuntime = $type;
		self::$suppress = $suppress;
		self::$missing = $full ? null : $states;
		self::$done = [];

		self::$isRunning = true;
	}

	public static function tick(): void
	{
		if (!self::$isRunning) {
			return;
		}

		if (self::$missing === null) {
			self::$isRunning = false;
			BlockInputData::from(array_keys(RuntimeBlockStateRegistry::getInstance()->getAllKnownStates()), self::$toRuntime);
			return;
		}

		$done = 0;
		foreach (self::$missing as $key => $state) {
			if ($done++ > self::MAX_PER_TICK) {
				break;
			}

			if (self::$toRuntime) {
				/** @var BlockStateData $state */
				try {
					$state = GlobalBlockStateHandlers::getUpgrader()->getBlockStateUpgrader()->upgrade($state);
					self::$done[$key] = GlobalBlockStateHandlers::getDeserializer()->deserialize($state);
				} catch (UnsupportedBlockStateException $e) {
					if (!self::$suppress) {
						EditThread::getInstance()->debug($e->getMessage());
					}
					self::$done[$key] = GlobalBlockStateHandlers::getDeserializer()->deserialize(GlobalBlockStateHandlers::getUnknownBlockStateData());
				}
			} else {
				/** @var int $state */
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