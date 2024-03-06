<?php

namespace platz1de\EasyEdit\convert\item;

use platz1de\EasyEdit\thread\EditThread;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\data\bedrock\EnchantmentIds;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\lang\Translatable;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use Throwable;

class EnchantmentItemConvertor extends ItemConvertorPiece
{
	private const JAVA_ENCHANTMENTS_TAG = "Enchantments";
	private const JAVA_STORED_ENCHANTMENTS_TAG = "StoredEnchantments";
	private const ENCHANTMENT_TAG = "id";
	private const ENCHANTMENT_LVL = "lvl";

	public function toBedrock(CompoundTag $item, CompoundTag $tag): void
	{
		$enchantments = $tag->getListTag(self::JAVA_ENCHANTMENTS_TAG);
		$storedEnchantments = $tag->getListTag(self::JAVA_STORED_ENCHANTMENTS_TAG);
		$tag->removeTag(self::JAVA_ENCHANTMENTS_TAG);
		$tag->removeTag(self::JAVA_STORED_ENCHANTMENTS_TAG);
		$enchs = [];
		if ($enchantments !== null && $enchantments->getTagType() === NBT::TAG_Compound) {
			foreach ($enchantments as $enchantment) {
				$enchs[] = $enchantment;
			}
		}
		if ($storedEnchantments !== null && $storedEnchantments->getTagType() === NBT::TAG_Compound) {
			foreach ($storedEnchantments as $enchantment) {
				$enchs[] = $enchantment;
			}
		}
		if (count($enchs) > 0) {
			$enchantmentList = [];
			/**
			 * @var CompoundTag $enchantment
			 */
			foreach ($enchs as $enchantment) {
				$name = $enchantment->getString(self::ENCHANTMENT_TAG, "");
				$level = $enchantment->getShort(self::ENCHANTMENT_LVL, 0);
				if ($name === "") {
					continue;
				}
				try {
					/** @var int $ench */
					$ench = constant(EnchantmentIds::class . "::" . strtoupper(str_replace(["minecraft:", "_curse"], ["", ""], $name)));
				} catch (Throwable) {
					continue;
				}
				$enchantmentList[] = CompoundTag::create()
					->setShort(self::ENCHANTMENT_TAG, $ench)
					->setShort(self::ENCHANTMENT_LVL, $level);
			}
			$tag->setTag(Item::TAG_ENCH, new ListTag($enchantmentList, NBT::TAG_Compound));
		}
	}

	public function toJava(CompoundTag $item, CompoundTag $tag): void
	{
		$enchantments = $item->getListTag(Item::TAG_ENCH);
		if ($enchantments !== null && $enchantments->getTagType() === NBT::TAG_Compound) {
			$enchantmentList = [];
			/**
			 * @var CompoundTag $enchantment
			 */
			foreach ($enchantments as $enchantment) {
				$id = $enchantment->getShort(self::ENCHANTMENT_TAG, -1);
				$level = $enchantment->getShort(self::ENCHANTMENT_LVL, 0);
				if ($id === -1) {
					continue;
				}
				$ench = EnchantmentIdMap::getInstance()->fromId($id);
				if ($ench === null) {
					continue;
				}
				$java = null;
				foreach (VanillaEnchantments::getAll() as $name => $enchant) {
					if ($enchant === $ench) {
						$java = $name . ($name === "VANISHING" || $name === "BINDING" ? "_curse" : "");
						break;
					}
				}
				if ($java === null) {
					$name = $ench->getName();
					if ($name instanceof Translatable) {
						$name = $name->getText();
					}
					EditThread::getInstance()->debug("Unknown enchantment: " . $name);
					continue;
				}
				$enchantmentList[] = CompoundTag::create()
					->setString(self::ENCHANTMENT_TAG, "minecraft:" . strtolower($java))
					->setShort(self::ENCHANTMENT_LVL, $level);
			}
			if ($item->getString("id") === "minecraft:enchanted_book") {
				$tag->setTag(self::JAVA_STORED_ENCHANTMENTS_TAG, new ListTag($enchantmentList, NBT::TAG_Compound));
			} else {
				$tag->setTag(self::JAVA_ENCHANTMENTS_TAG, new ListTag($enchantmentList, NBT::TAG_Compound));
			}
		}
	}
}