<?php

namespace platz1de\EasyEdit\world\blockupdate;

use BadMethodCallException;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;

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

    protected function decodePayload(ByteBufferReader $in) : void
    {
        throw new BadMethodCallException("Injectors should never be decoded");
    }

    protected function encodePayload(ByteBufferWriter $out) : void
    {
        $bytes = $this->rawData;
        $len = strlen($bytes);
        for ($i = 0; $i < $len; ++$i) {
            Byte::writeUnsigned($out, ord($bytes[$i]));
        }
    }

	public function handle(PacketHandlerInterface $handler): bool
	{
		throw new BadMethodCallException("Cannot handle injectors");
	}
}