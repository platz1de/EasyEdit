<?php

namespace platz1de\EasyEdit\world\blockupdate;

use pocketmine\network\mcpe\convert\GlobalItemTypeDictionary;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializerContext;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\utils\Binary;

class InjectingData
{
	private PacketSerializer $injection;
	private int $blockCount = 0;
	private BlockPosition $position;

	public function __construct(int $x, int $y, int $z)
	{
		$this->position = new BlockPosition($x, $y, $z);
		$this->injection = PacketSerializer::encoder(new PacketSerializerContext(GlobalItemTypeDictionary::getInstance()->getDictionary()));
	}

	public function writeBlock(int $x, int $y, int $z, int $id): void
	{
		$this->blockCount++;
		$this->injection->putVarInt($x);
		$this->injection->putUnsignedVarInt(Binary::unsignInt($y));
		$this->injection->putVarInt($z);
		$this->injection->putUnsignedVarInt(RuntimeBlockMapping::getInstance()->toRuntimeId($id));
		$this->injection->putUnsignedVarInt(2); //network flag
		$this->injection->putUnsignedVarLong(-1); //we don't have any actors
		$this->injection->putUnsignedVarInt(0); //not synced
	}

	public function toProtocol(): string
	{
		$serializer = PacketSerializer::encoder(new PacketSerializerContext(GlobalItemTypeDictionary::getInstance()->getDictionary()));
		$serializer->putBlockPosition($this->position);
		$serializer->putUnsignedVarInt($this->blockCount);
		$serializer->put($this->injection->getBuffer());
		$serializer->putUnsignedVarInt(0); //we don't use the second layer
		return $serializer->getBuffer();
	}
}