<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use platz1de\EasyEdit\selection\cubic\CubicChunkLoader;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\math\Vector3;

//TODO: remove this
class SchematicLoadDummy extends BlockListSelection
{
	use CubicChunkLoader;

	private string $path;

	/**
	 * BlockListSelection constructor.
	 * @param string $player
	 * @param string $world
	 * @param string $path
	 */
	public function __construct(string $player, string $world = "", string $path = "")
	{
		parent::__construct($player, $world, new Vector3(0, 0, 0), new Vector3(0, 0, 0));

		$this->path = $path;
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);

		$stream->putString($this->path);
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);

		$this->path = $stream->getString();
	}

	public function useOnBlocks(Vector3 $place, Closure $closure, SelectionContext $context): void
	{
		//how
	}

	/**
	 * @return string
	 */
	public function getPath(): string
	{
		return $this->path;
	}
}