<?php

namespace platz1de\EasyEdit\convert\tile;

use Error;
use JsonException;
use pocketmine\block\tile\Sign;
use pocketmine\block\utils\DyeColor;
use pocketmine\color\Color;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\Binary;
use UnexpectedValueException;

class SignConvertor extends TileConvertorPiece
{
	private const JAVA_TAG_GLOWING = "GlowingText";
	private const JAVA_TAG_COLOR = "Color";

	public function preprocessTileState(BlockStateData $state): ?CompoundTag
	{
		return null;
	}

	public function toBedrock(CompoundTag $tile): void
	{
		$lines = [];
		for ($i = 1; $i <= 4; $i++) {
			$tag = sprintf(Sign::TAG_TEXT_LINE, $i);
			$line = $tile->getString($tag);
			try {
				/** @var string[] $json */
				$json = json_decode($line, true, 2, JSON_THROW_ON_ERROR);
				if (!isset($json["text"])) {
					throw new JsonException("Missing text key");
				}
				$text = $json["text"];
			} catch (JsonException) {
				throw new UnexpectedValueException("Invalid JSON in sign text: " . $line);
			}
			$tile->setString($tag, $text);
			$lines[] = $text;
		}
		$tile->setString(Sign::TAG_TEXT_BLOB, implode("\n", $lines));

		$tile->setByte(Sign::TAG_GLOWING_TEXT, $tile->getByte(self::JAVA_TAG_GLOWING, 0));
		$tile->removeTag(self::JAVA_TAG_GLOWING);

		/** @see Sign::TAG_LEGACY_BUG_RESOLVE */
		$tile->setByte(Sign::TAG_LEGACY_BUG_RESOLVE, 1);

		$javaColor = $tile->getString(self::JAVA_TAG_COLOR, "black");
		try {
			/**
			 * @var DyeColor $color
			 */
			$color = DyeColor::__callStatic($javaColor, []);
		} catch (Error) {
			throw new UnexpectedValueException("Invalid color: " . $javaColor);
		}
		$tile->setInt(Sign::TAG_TEXT_COLOR, Binary::signInt($color->getRgbValue()->toARGB()));
		$tile->removeTag(self::JAVA_TAG_COLOR);
	}

	public function toJava(CompoundTag $tile, BlockStateData $state): ?BlockStateData
	{
		for ($i = 1; $i <= 4; $i++) {
			$tag = sprintf(Sign::TAG_TEXT_LINE, $i);
			$line = $tile->getString($tag);
			try {
				/** @var string $json */
				$json = json_encode(["text" => $line], JSON_THROW_ON_ERROR);
			} catch (JsonException) {
				throw new UnexpectedValueException("Failed to encode JSON for sign text: " . $line);
			}
			$tile->setString($tag, $json);
		}
		$tile->removeTag(Sign::TAG_TEXT_BLOB);

		$tile->setByte(self::JAVA_TAG_GLOWING, $tile->getByte(Sign::TAG_GLOWING_TEXT, 0));
		$tile->removeTag(Sign::TAG_GLOWING_TEXT);
		$tile->removeTag(Sign::TAG_LEGACY_BUG_RESOLVE);

		$color = Color::fromARGB(Binary::unsignInt($tile->getInt(Sign::TAG_TEXT_COLOR)));
		//Find the closest matching dye color (we sadly loose data here, java doesn't support custom colors though)
		$closest = null;
		$closestDistance = PHP_INT_MAX;
		foreach (DyeColor::getAll() as $dye) {
			$dyeColor = $dye->getRgbValue();
			$red = $color->getR() - $dyeColor->getR();
			$green = $color->getG() - $dyeColor->getG();
			$blue = $color->getB() - $dyeColor->getB();
			$alpha = $color->getA() - $dyeColor->getA();
			$distance = $red * $red + $green * $green + $blue * $blue + $alpha * $alpha;
			if ($distance === 0) {
				$closest = $dye;
				break;
			}
			if ($distance < $closestDistance) {
				$closest = $dye;
				$closestDistance = $distance;
			}
		}
		if ($closest === null) {
			throw new UnexpectedValueException("Failed to find the closest dye color for color: rgba(" . $color->getR() . ", " . $color->getG() . ", " . $color->getB() . ", " . $color->getA() . ")");
		}
		$tile->setString(self::JAVA_TAG_COLOR, strtolower($closest->name()));
		$tile->removeTag(Sign::TAG_TEXT_COLOR);
		return null;
	}
}