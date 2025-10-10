<?php

namespace platz1de\EasyEdit\world\blockupdate;

use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\utils\Binary;

class InjectingData
{
	private ByteBufferWriter $injection;
	private int $blockCount = 0;
	private BlockPosition $position;

	public function __construct(int $x, int $y, int $z)
	{
		$this->position = new BlockPosition($x, $y, $z);
		$this->injection = new ByteBufferWriter();
	}

	public function writeBlock(int $x, int $y, int $z, int $id): void
	{
		$this->blockCount++;
		VarInt::writeSignedInt($this->injection, $x);
		VarInt::writeUnsignedInt($this->injection, Binary::unsignInt($y));
		VarInt::writeSignedInt($this->injection, $z);
		VarInt::writeUnsignedInt($this->injection, TypeConverter::getInstance()->getBlockTranslator()->internalIdToNetworkId($id));
		VarInt::writeUnsignedInt($this->injection, 2); //network flag
		VarInt::writeUnsignedLong($this->injection, 0); //we don't have any actors
		VarInt::writeUnsignedInt($this->injection, 0); //not synced
	}

	public function toProtocol(): string
	{
		$serializer = new ByteBufferWriter();
		CommonTypes::putBlockPosition($serializer, $this->position);
		VarInt::writeUnsignedInt($serializer, $this->blockCount);
		$serializer->writeByteArray($this->injection->getData());
		VarInt::writeUnsignedInt($serializer, 0); //we don't use the second layer
		return $serializer->getData();
	}
}