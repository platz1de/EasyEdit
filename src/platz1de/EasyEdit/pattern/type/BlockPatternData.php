<?php

namespace platz1de\EasyEdit\pattern\type;

use platz1de\EasyEdit\pattern\block\BlockType;
use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

trait BlockPatternData
{
	private BlockType $block;

	/**
	 * @param BlockType $block
	 * @param Pattern[] $pieces
	 */
	public function __construct(BlockType $block, array $pieces)
	{
		parent::__construct($pieces);
		$this->block = $block;
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->block->fastSerialize());
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->block = BlockType::fastDeserialize($stream->getString());
	}
}