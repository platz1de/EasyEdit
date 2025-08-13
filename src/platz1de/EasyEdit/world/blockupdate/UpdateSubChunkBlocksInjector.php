<?php

namespace platz1de\EasyEdit\world\blockupdate;

use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
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

	protected function decodePayload(PacketSerializer $in): void
	{
		$prev = $in->getOffset();
		$mock = new UpdateSubChunkBlocksPacket();
		$mock->decodePayload($in);
		$this->rawData = substr($in->getBuffer(), $prev, $in->getOffset() - $prev);
	}

	protected function encodePayload(PacketSerializer $out): void
	{
		$out->put($this->rawData);
	}

	public function handle(PacketHandlerInterface $handler): bool
	{
		//Apparently some plugins just blindly handle packets sent to the network, so we need to emulate its behavior
		$mock = new UpdateSubChunkBlocksPacket();
		$mock->decodePayload(PacketSerializer::decoder($this->rawData, 0));
		return $mock->handle($handler);
	}
}