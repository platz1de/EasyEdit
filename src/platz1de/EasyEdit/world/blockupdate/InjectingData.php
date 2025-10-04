<?php

namespace platz1de\EasyEdit\world\blockupdate;

use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\utils\Binary;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;

class InjectingData
{
    private BlockPosition $position;
    private int $blockCount = 0;

    /** @var list<array{x:int,y:int,z:int,id:int}> */
    private array $blocks = [];

    public function __construct(int $x, int $y, int $z)
    {
        $this->position = new BlockPosition($x, $y, $z);
    }

    public function writeBlock(int $x, int $y, int $z, int $id): void
    {
        $this->blockCount++;
        $this->blocks[] = ['x' => $x, 'y' => $y, 'z' => $z, 'id' => $id];
    }

    public function toProtocol(): string
    {
        $out = new ByteBufferWriter();

        CommonTypes::putSignedBlockPosition($out, $this->position);
        VarInt::writeUnsignedInt($out, $this->blockCount);

        foreach ($this->blocks as $b) {
            VarInt::writeSignedInt($out, $b['x']);
            VarInt::writeUnsignedInt($out, Binary::unsignInt($b['y']));
            VarInt::writeSignedInt($out, $b['z']);

            $runtimeId = TypeConverter::getInstance()->getBlockTranslator()->internalIdToNetworkId($b['id']);
            VarInt::writeUnsignedInt($out, $runtimeId);

            VarInt::writeUnsignedInt($out, 2);
            VarInt::writeUnsignedLong($out, -1);
            VarInt::writeUnsignedInt($out, 0);
        }

        VarInt::writeUnsignedInt($out, 0);
        return $out->getData();
    }
}