<?php

namespace platz1de\EasyEdit\world\blockupdate;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\UpdateSubChunkBlocksPacket;

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

	public static function create(string $data): self
	{
		$result = new self;
		$result->rawData = $data;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in): void
	{
		$prev = $in->getOffset();
		$mock = new UpdateSubChunkBlocksPacket();
		$mock->decodePayload($in);
		$this->rawData = substr($in->getData(), $prev, $in->getOffset() - $prev);
	}

	protected function encodePayload(ByteBufferWriter $out): void
	{
		$out->writeByteArray($this->rawData);
	}

	public function handle(PacketHandlerInterface $handler): bool
	{
		//Apparently some plugins just blindly handle packets sent to the network, so we need to emulate its behavior
		$mock = new UpdateSubChunkBlocksPacket();
		$mock->decodePayload(new ByteBufferReader($this->rawData));
		return $mock->handle($handler);
	}
}