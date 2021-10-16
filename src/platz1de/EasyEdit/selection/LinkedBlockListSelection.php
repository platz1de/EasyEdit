<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use platz1de\EasyEdit\selection\cubic\CubicChunkLoader;
use platz1de\EasyEdit\thread\modules\StorageModule;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\math\Vector3;

class LinkedBlockListSelection extends Selection
{
	use CubicChunkLoader;

	private int $id;

	/**
	 * BlockListSelection constructor.
	 * @param string $player
	 * @param string $world
	 * @param int    $id
	 */
	public function __construct(string $player, string $world = "", int $id = 0)
	{
		parent::__construct($player, $world, new Vector3(0, 0, 0), new Vector3(0, 0, 0));

		$this->id = $id;
	}

	/**
	 * @return BlockListSelection
	 */
	public function get(): BlockListSelection
	{
		return StorageModule::getStored($this->id);
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);

		$stream->putInt($this->id);
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);

		$this->id = $stream->getInt();
	}

	public function useOnBlocks(Vector3 $place, Closure $closure, SelectionContext $context): void
	{
		//how
	}
}