<?php

namespace platz1de\EasyEdit\schematic\type;

use platz1de\EasyEdit\convert\LegacyBlockIdConvertor;
use platz1de\EasyEdit\math\BlockOffsetVector;
use platz1de\EasyEdit\math\BlockVector;
use platz1de\EasyEdit\schematic\nbt\AbstractByteArrayTag;
use platz1de\EasyEdit\schematic\nbt\AbstractNBTSerializer;
use platz1de\EasyEdit\schematic\nbt\BigEndianAbstractNBTSerializer;
use platz1de\EasyEdit\selection\DynamicBlockListSelection;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\InternetException;
use pocketmine\world\World;
use UnexpectedValueException;

class McEditSchematic extends SchematicType
{
	public const OFFSET_X = "WEOffsetX";
	public const OFFSET_Y = "WEOffsetY";
	public const OFFSET_Z = "WEOffsetZ";
	public const BLOCK_ID = "Blocks";
	public const BLOCK_META = "Data";

	public static function readIntoSelection(CompoundTag $nbt, DynamicBlockListSelection $target): void
	{
		if (!LegacyBlockIdConvertor::isAvailable()) {
			throw new InternetException("Couldn't load needed data files");
		}
		$xSize = $nbt->getShort(self::TAG_WIDTH);
		$ySize = $nbt->getShort(self::TAG_HEIGHT);
		$zSize = $nbt->getShort(self::TAG_LENGTH);
		$target->setPoint(new BlockOffsetVector($nbt->getInt(self::OFFSET_X, 0), $nbt->getInt(self::OFFSET_Y, 0) - World::Y_MIN, $nbt->getInt(self::OFFSET_Z, 0)));
		$target->setPos1(new BlockVector(0, World::Y_MIN, 0));
		$target->setPos2(new BlockVector($xSize - 1, World::Y_MIN + $ySize - 1, $zSize - 1));
		$target->getManager()->loadBetween($target->getPos1(), $target->getPos2());

		//"AddBlocks" allows ids over 255
		//this can be ignored as java pre-flattening only had 255 block ids in use and later didn't support block ids at all
		$blockIdData = $nbt->getTag(self::BLOCK_ID);
		$blockMetaData = $nbt->getTag(self::BLOCK_META);

		if (!$blockIdData instanceof AbstractByteArrayTag || !$blockMetaData instanceof AbstractByteArrayTag) {
			throw new UnexpectedValueException("Invalid schematic file");
		}

		$blockIdData->optimizeHighFrequencyAccess();
		$blockMetaData->optimizeHighFrequencyAccess();

		$blockIdChunk = "";
		$blockMetaChunk = "";
		$blockCache = [];
		$ySize += World::Y_MIN;
		$i = 0;
		//McEdit why this weird order?
		for ($y = World::Y_MIN; $y < $ySize; ++$y) {
			for ($z = 0; $z < $zSize; ++$z) {
				for ($x = 0; $x < $xSize; ++$x) {
					if ($i % AbstractByteArrayTag::CHUNK_SIZE === 0) {
						$blockIdChunk = $blockIdData->nextChunk();
						$blockMetaChunk = $blockMetaData->nextChunk();
					}
					$id = ord($blockIdChunk[$i % AbstractByteArrayTag::CHUNK_SIZE]);
					$meta = ord($blockMetaChunk[$i % AbstractByteArrayTag::CHUNK_SIZE]);

					$j = $id << LegacyBlockIdConvertor::METADATA_BITS | $meta;
					if (!isset($blockCache[$j])) {
						$blockCache[$j] = LegacyBlockIdConvertor::convertFromJava($j);
					}

					$target->addBlock($x, $y, $z, $blockCache[$j]);
					$i++;
				}
			}
		}

		$blockIdData->close();
		$blockMetaData->close();

		//TODO: tiles and entities
	}

	public static function getNbtSerializer(): AbstractNBTSerializer
	{
		return new BigEndianAbstractNBTSerializer();
	}
}