<?php

namespace platz1de\EasyEdit\pattern;

use platz1de\EasyEdit\pattern\block\StaticBlock;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;

class PatternArgumentData
{
	private bool $xAxis = false;
	private bool $yAxis = false;
	private bool $zAxis = false;
	private StaticBlock $block;
	private Block $realBlock;
	/**
	 * @var int[]
	 */
	private array $intData = [];

	/**
	 * @return bool
	 */
	public function checkXAxis(): bool
	{
		return $this->xAxis;
	}

	/**
	 * @return $this
	 */
	public function useXAxis(): PatternArgumentData
	{
		$this->xAxis = true;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function checkYAxis(): bool
	{
		return $this->yAxis;
	}

	/**
	 * @return $this
	 */
	public function useYAxis(): PatternArgumentData
	{
		$this->yAxis = true;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function checkZAxis(): bool
	{
		return $this->zAxis;
	}

	/**
	 * @return $this
	 */
	public function useZAxis(): PatternArgumentData
	{
		$this->zAxis = true;
		return $this;
	}

	/**
	 * @return StaticBlock
	 */
	public function getBlock(): StaticBlock
	{
		return $this->block;
	}

	/**
	 * @return $this
	 */
	public function setBlock(StaticBlock $block): PatternArgumentData
	{
		$this->block = $block;
		return $this;
	}

	/**
	 * @return Block
	 */
	public function getRealBlock(): Block
	{
		return $this->realBlock;
	}

	/**
	 * @return $this
	 */
	public function setRealBlock(Block $block): PatternArgumentData
	{
		$this->realBlock = $block;
		return $this;
	}

	/**
	 * @param string $name
	 * @return int
	 */
	public function getInt(string $name): int
	{
		return $this->intData[$name] ?? -1;
	}

	/**
	 * @param string $name
	 * @param int    $int
	 * @return $this
	 */
	public function setInt(string $name, int $int): PatternArgumentData
	{
		$this->intData[$name] = $int;
		return $this;
	}

	/**
	 * @param array<int, mixed> $args
	 * @return PatternArgumentData
	 */
	public function parseAxes(array &$args): PatternArgumentData
	{
		$result = new self;
		$result->xAxis = in_array("x", $args, true);
		$result->yAxis = in_array("y", $args, true);
		$result->zAxis = in_array("z", $args, true);

		$args = array_diff($args, ["x", "y", "z"]);

		return $result;
	}

	/**
	 * @return PatternArgumentData
	 */
	public static function create(): PatternArgumentData
	{
		return new self;
	}

	/**
	 * @param string $block
	 * @return PatternArgumentData
	 */
	public static function fromBlockType(string $block): PatternArgumentData
	{
		if ($block === "") {
			return new self;
		}
		return self::create()->setBlock(PatternParser::getBlockType($block));
	}

	/**
	 * @return string
	 */
	public function fastSerialize(): string
	{
		$stream = new ExtendedBinaryStream();

		$stream->putBool($this->xAxis);
		$stream->putBool($this->yAxis);
		$stream->putBool($this->zAxis);

		if (isset($this->block)) {
			$stream->putBool(true);
			$stream->putString($this->block->fastSerialize());
		} else {
			$stream->putBool(false);
		}

		if (isset($this->realBlock)) {
			$stream->putBool(true);
			$stream->putInt($this->realBlock->getFullId());
		} else {
			$stream->putBool(false);
		}

		$stream->putInt(count($this->intData));
		foreach ($this->intData as $name => $int) {
			$stream->putString($name);
			$stream->putInt($int);
		}

		return $stream->getBuffer();
	}

	/**
	 * @param string $data
	 * @return PatternArgumentData
	 */
	public static function fastDeserialize(string $data): PatternArgumentData
	{
		$stream = new ExtendedBinaryStream($data);
		$result = new self;

		$result->xAxis = $stream->getBool();
		$result->yAxis = $stream->getBool();
		$result->zAxis = $stream->getBool();

		if ($stream->getBool()) {
			/** @phpstan-ignore-next-line */
			$result->block = Pattern::fastDeserialize($stream->getString());
		}

		if ($stream->getBool()) {
			$result->realBlock = BlockFactory::getInstance()->fromFullBlock($stream->getInt());
		}

		for ($i = $stream->getInt(); $i > 0; $i--) {
			$result->intData[$stream->getString()] = $stream->getInt();
		}

		return $result;
	}
}