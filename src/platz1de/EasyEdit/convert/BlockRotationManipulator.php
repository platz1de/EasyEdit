<?php

namespace platz1de\EasyEdit\convert;

use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\utils\BlockParser;
use platz1de\EasyEdit\utils\MixedUtils;
use pocketmine\math\Axis;
use Throwable;
use UnexpectedValueException;

/**
 * Manages rotation and flipping of blocks
 */
class BlockRotationManipulator
{
	/**
	 * @var array<int, int>
	 */
	private static array $rotationData;
	/**
	 * @var array<int, array<int, int>>
	 */
	private static array $flipData;
	private static bool $available = false;

	public static function load(string $rotationSource, string $flipSource): void
	{
		self::$rotationData = [];
		self::$flipData = [];

		try {
			/** @var string $pastRotationId */
			foreach (MixedUtils::getJsonData($rotationSource, 2) as $preRotationId => $pastRotationId) {
				self::$rotationData[BlockParser::fromStringId($preRotationId)] = BlockParser::fromStringId($pastRotationId);
			}
			/** @var array<string, string> $axisFlips */
			foreach (MixedUtils::getJsonData($flipSource, 3) as $axisName => $axisFlips) {
				$axis = match ($axisName) {
					"xAxis" => Axis::X,
					"yAxis" => Axis::Y,
					"zAxis" => Axis::Z,
					default => throw new UnexpectedValueException("Unknown axis name $axisName")
				};
				foreach ($axisFlips as $preFlipId => $pastFlipId) {
					self::$flipData[$axis][BlockParser::fromStringId($preFlipId)] = BlockParser::fromStringId($pastFlipId);
				}
			}
			self::$available = true;
		} catch (Throwable $e) {
			EditThread::getInstance()->getLogger()->error("Failed to parse conversion data, schematic conversion is not available");
			EditThread::getInstance()->getLogger()->logException($e);
		}
	}

	/**
	 * @param int $id
	 * @return int
	 */
	public static function rotate(int $id): int
	{
		return self::$rotationData[$id] ?? $id;
	}

	/**
	 * @param int $axis
	 * @param int $id
	 * @return int
	 */
	public static function flip(int $axis, int $id): int
	{
		return self::$flipData[$axis][$id] ?? $id;
	}

	/**
	 * @return bool
	 */
	public static function isAvailable(): bool
	{
		return self::$available;
	}
}