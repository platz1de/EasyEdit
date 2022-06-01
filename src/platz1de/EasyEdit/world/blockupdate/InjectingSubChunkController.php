<?php

namespace platz1de\EasyEdit\world\blockupdate;

use platz1de\EasyEdit\world\ChunkController;
use pocketmine\world\World;

class InjectingSubChunkController extends ChunkController
{
	/**
	 * @var InjectingData[]
	 */
	private array $injections = [];
	private InjectingData $currentInjection;

	/**
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @return bool
	 */
	public function moveTo(int $x, int $y, int $z): bool
	{
		$return = parent::moveTo($x, $y, $z);
		if ($return) {
			$index = World::blockHash($this->currentX, $this->currentY, $this->currentZ);
			if (isset($this->injections[$index])) {
				$this->currentInjection = $this->injections[$index];
			} else {
				$this->injections[$index] = $this->currentInjection = new InjectingData($this->currentX, $this->currentY, $this->currentZ);
			}
		}
		return $return;
	}

	/**
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @param int $block
	 */
	public function setBlock(int $x, int $y, int $z, int $block): void
	{
		parent::setBlock($x, $y, $z, $block);
		$this->currentInjection->writeBlock($x, $y, $z, $block);
	}

	/**
	 * @return InjectingData[]
	 */
	public function getInjections(): array
	{
		return $this->injections;
	}

	/**
	 * @param int $chunk
	 * @return InjectingData[]
	 */
	public function getInjection(int $chunk): array
	{
		World::getXZ($chunk, $x, $z);
		$injections = [];
		for ($i = World::Y_MIN >> 4; $i <= World::Y_MAX >> 4; ++$i) {
			$index = World::blockHash($x, $i, $z);
			if (isset($this->injections[$index])) {
				$injections[$index] = $this->injections[$index];
			}
		}
		return $injections;
	}
}