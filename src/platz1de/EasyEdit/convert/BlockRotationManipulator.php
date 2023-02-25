<?php

namespace platz1de\EasyEdit\convert;

use InvalidArgumentException;
use platz1de\EasyEdit\convert\block\BlockRotationTranslator;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\utils\RepoManager;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\math\Axis;
use Throwable;
use UnexpectedValueException;

/**
 * Manages rotation and flipping of blocks
 */
class BlockRotationManipulator
{
	/**
	 * @var array<string, BlockRotationTranslator>
	 */
	private static array $convertors;
	private static bool $available = false;

	public static function load(): void
	{
		self::$convertors = [];

		try {
			foreach (RepoManager::getJson("manipulation-data", 5) as $type => $data) {
				if (!is_array($data)) {
					throw new UnexpectedValueException("Invalid data for $type");
				}
				self::$convertors[$type] = new BlockRotationTranslator($data);
			}
			self::$available = true;
		} catch (Throwable $e) {
			EditThread::getInstance()->getLogger()->error("Failed to parse rotation data, block rotating is not available");
			EditThread::getInstance()->getLogger()->debug($e->getMessage());
		}
	}

	/**
	 * @param BlockStateData $state
	 * @return BlockStateData
	 */
	public static function rotate(BlockStateData $state): BlockStateData
	{
		$converter = self::$convertors[$state->getName()] ?? null;
		if ($converter === null) {
			return $state;
		}
		return $converter->rotate($state);
	}

	/**
	 * @param int            $axis
	 * @param BlockStateData $state
	 * @return BlockStateData
	 */
	public static function flip(int $axis, BlockStateData $state): BlockStateData
	{
		$converter = self::$convertors[$state->getName()] ?? null;
		if ($converter === null) {
			return $state;
		}
		return match ($axis) {
			Axis::X => $converter->flipX($state),
			Axis::Y => $converter->flipY($state),
			Axis::Z => $converter->flipZ($state),
			default => throw new InvalidArgumentException("Invalid axis $axis"),
		};
	}

	/**
	 * @return bool
	 */
	public static function isAvailable(): bool
	{
		return self::$available;
	}
}