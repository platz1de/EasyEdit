<?php

namespace platz1de\EasyEdit\convert\item;

use pocketmine\color\Color;
use pocketmine\item\Armor;
use pocketmine\item\Item;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\Binary;
use Throwable;

class DisplayItemConvertor extends ItemConvertorPiece
{
	public function toBedrock(CompoundTag $item, CompoundTag $tag): void
	{
		$display = $tag->getCompoundTag(Item::TAG_DISPLAY);
		if ($display !== null) {
			$name = $display->getString(Item::TAG_DISPLAY_NAME);
			if ($name !== "") {
				try {
					/**
					 * @var array<string, string> $json
					 */
					$json = json_decode($name, true, 512, JSON_THROW_ON_ERROR);
				} catch (Throwable) {
					$json = ["text" => $name];
				}
				$item->setString(Item::TAG_DISPLAY_NAME, $json["text"] ?? $name);
			}

			$lore = $display->getListTag(Item::TAG_DISPLAY_LORE);
			if ($lore !== null && $lore->getTagType() === NBT::TAG_String) {
				$loreArray = [];
				/**
				 * @var StringTag $line
				 */
				foreach ($lore as $line) {
					try {
						/**
						 * @var array<string, string> $json
						 */
						$json = json_decode($line->getValue(), true, 512, JSON_THROW_ON_ERROR);
					} catch (Throwable) {
						$json = ["text" => $line];
					}
					$loreArray[] = new StringTag($json["text"] ?? $line);
				}
				$item->setTag(Item::TAG_DISPLAY_LORE, new ListTag($loreArray, NBT::TAG_String));
			}

			try {
				$colorCode = $display->getInt("color");
				$color = Color::fromRGB(Binary::unsignInt($colorCode));
				$tag->setInt(Armor::TAG_CUSTOM_COLOR, Binary::signInt($color->toARGB()));
			} catch (Throwable) {
			}
			$display->removeTag("color");
		}
	}

	public function toJava(CompoundTag $item, CompoundTag $tag): void
	{
		$display = $tag->getCompoundTag(Item::TAG_DISPLAY);
		if ($display !== null) {
			$name = $display->getString(Item::TAG_DISPLAY_NAME);
			if ($name !== "") {
				try {
					$display->setString(Item::TAG_DISPLAY_NAME, json_encode(["text" => $name], JSON_THROW_ON_ERROR));
				} catch (Throwable) {
				}
			}

			$lore = $display->getListTag(Item::TAG_DISPLAY_LORE);
			if ($lore !== null && $lore->getTagType() === NBT::TAG_String) {
				$loreArray = [];
				/**
				 * @var StringTag $line
				 */
				foreach ($lore as $line) {
					try {
						$loreArray[] = new StringTag(json_encode(["text" => $line->getValue()], JSON_THROW_ON_ERROR));
					} catch (Throwable) {
					}
				}
				$display->setTag(Item::TAG_DISPLAY_LORE, new ListTag($loreArray, NBT::TAG_String));
			}

			try {
				$colorCode = $tag->getInt(Armor::TAG_CUSTOM_COLOR);
				$color = Color::fromARGB(Binary::unsignInt($colorCode));
				$display->setInt("color", Binary::signInt(($color->getR() << 16) | ($color->getG() << 8) | $color->getB()));
			} catch (Throwable) {
			}
		}
	}
}