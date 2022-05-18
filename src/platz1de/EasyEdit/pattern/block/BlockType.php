<?php

namespace platz1de\EasyEdit\pattern\block;

use platz1de\EasyEdit\pattern\parser\WrongPatternUsageException;
use platz1de\EasyEdit\pattern\Pattern;

abstract class BlockType extends Pattern
{
	public function __construct()
	{
		parent::__construct([]);
	}

	/**
	 * @param int $fullBlock
	 * @return bool
	 */
	abstract public function equals(int $fullBlock): bool;

	/**
	 * @param string $data
	 * @return BlockType
	 */
	public static function fastDeserialize(string $data): BlockType
	{
		$block = parent::fastDeserialize($data);
		if (!$block instanceof self) {
			throw new WrongPatternUsageException("Expected a block pattern, got " . get_class($block));
		}
		return $block;
	}
}