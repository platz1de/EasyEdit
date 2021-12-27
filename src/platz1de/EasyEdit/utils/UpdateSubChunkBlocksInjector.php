<?php

namespace platz1de\EasyEdit\utils;

use BadMethodCallException;
use pocketmine\network\mcpe\convert\GlobalItemTypeDictionary;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializerContext;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\utils\Binary;

/**
 * We inject our pre-generated packet data directly into the network sending to not require creation of (way too many) packet entries
 *
 * Warning: If the packet syntax changes, this will silently break, not displaying any changes to the client
 */
class UpdateSubChunkBlocksInjector extends DataPacket implements ClientboundPacket
{
	public const NETWORK_ID = ProtocolInfo::UPDATE_SUB_CHUNK_BLOCKS_PACKET;

	/**
	 * Binary data to inject
	 */
	private string $rawData;

	public static function writeBlock(PacketSerializer $serializer, int $x, int $y, int $z, int $id): void
	{
		$serializer->putVarInt($x);
		$serializer->putUnsignedVarInt(Binary::unsignInt($y));
		$serializer->putVarInt($z);
		$serializer->putUnsignedVarInt(RuntimeBlockMapping::getInstance()->toRuntimeId($id));
		$serializer->putUnsignedVarInt(2); //network flag
		$serializer->putUnsignedVarLong(-1); //we don't have any actors
		$serializer->putUnsignedVarInt(0); //not synced
	}

	public static function getDataFrom(int $x, int $y, int $z, int $changedBlockAmount, string $changedBlocks): string
	{
		$serializer = PacketSerializer::encoder(new PacketSerializerContext(GlobalItemTypeDictionary::getInstance()->getDictionary()));
		$serializer->putBlockPosition(new BlockPosition($x << 4, $y << 4, $z << 4));
		$serializer->putUnsignedVarInt($changedBlockAmount);
		$serializer->put($changedBlocks);
		$serializer->putUnsignedVarInt(0); //we don't use the second layer
		return $serializer->getBuffer();
	}

	public static function create(string $data): self
	{
		$result = new self;
		$result->rawData = $data;
		return $result;
	}


	protected function decodePayload(PacketSerializer $in): void
	{
		throw new BadMethodCallException("Injectors should never be decoded");
	}

	protected function encodePayload(PacketSerializer $out): void
	{
		$out->put($this->rawData);
	}

	public function handle(PacketHandlerInterface $handler): bool
	{
		throw new BadMethodCallException("Cannot handle injectors");
	}
}