<?php

namespace platz1de\EasyEdit\utils;

use pocketmine\network\mcpe\convert\GlobalItemTypeDictionary;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializerContext;
use pocketmine\world\utils\SubChunkExplorerStatus;
use pocketmine\world\World;

class InjectingSubChunkExplorer extends SafeSubChunkExplorer
{
	/**
	 * @var PacketSerializer[]
	 */
	private array $injections = [];
	private PacketSerializer $currentInjection;
	private int $currentIndex;
	/**
	 * @var int[]
	 */
	private array $blockCounts = [];
	private int $currentBlockCount = 0;

	/**
	 * @phpstan-return SubChunkExplorerStatus::*
	 */
	public function moveTo(int $x, int $y, int $z): int
	{
		$return = parent::moveTo($x, $y, $z);
		if ($return === SubChunkExplorerStatus::MOVED) {
			if (isset($this->currentInjection)) {
				$this->injections[$this->currentIndex] = $this->currentInjection;
				$this->blockCounts[$this->currentIndex] = $this->currentBlockCount;
			}
			$this->currentIndex = World::blockHash($x >> 4, $y >> 4, $z >> 4);
			$this->currentInjection = $this->injections[$this->currentIndex] ?? PacketSerializer::encoder(new PacketSerializerContext(GlobalItemTypeDictionary::getInstance()->getDictionary()));
			$this->currentBlockCount = $this->blockCounts[$this->currentIndex] ?? 0;
		}
		return $return;
	}

	/**
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @param int $block
	 */
	public function setBlockAt(int $x, int $y, int $z, int $block): void
	{
		parent::setBlockAt($x, $y, $z, $block);
		UpdateSubChunkBlocksInjector::writeBlock($this->currentInjection, $x, $y, $z, $block);
		$this->currentBlockCount++;
	}

	/**
	 * @return array{PacketSerializer[], int[]}
	 */
	public function getInjections(): array
	{
		if (!isset($this->currentIndex)) {
			return [[], []];
		}
		$this->injections[$this->currentIndex] = $this->currentInjection;
		$this->blockCounts[$this->currentIndex] = $this->currentBlockCount;
		return [$this->injections, $this->blockCounts];
	}
}