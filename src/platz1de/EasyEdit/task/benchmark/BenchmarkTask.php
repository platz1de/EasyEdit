<?php

namespace platz1de\EasyEdit\task\benchmark;

use pocketmine\scheduler\Task;
use pocketmine\Server;

class BenchmarkTask extends Task
{
	/**
	 * @var int
	 */
	private $ticks;
	/**
	 * @var float
	 */
	private $tpsTotal;
	/**
	 * @var float
	 */
	private $tpsMin = 20;
	/**
	 * @var float
	 */
	private $loadTotal;
	/**
	 * @var float
	 */
	private $loadMax = 0;

	public function onRun(): void
	{
		$this->ticks++;
		$tps = Server::getInstance()->getTicksPerSecond();
		$load = Server::getInstance()->getTickUsage();

		$this->tpsTotal += $tps;
		if ($this->tpsMin > $tps) {
			$this->tpsMin = $tps;
		}
		$this->loadTotal += $load;
		if ($this->loadMax < $load) {
			$this->loadMax = $load;
		}
	}

	public function onCancel(): void
	{
		$this->tpsTotal /= $this->ticks;
		$this->loadTotal /= $this->ticks;
	}

	/**
	 * @return float
	 */
	public function getTpsTotal(): float
	{
		return $this->tpsTotal;
	}

	/**
	 * @return float
	 */
	public function getTpsMin(): float
	{
		return $this->tpsMin;
	}

	/**
	 * @return float
	 */
	public function getLoadTotal(): float
	{
		return $this->loadTotal;
	}

	/**
	 * @return float
	 */
	public function getLoadMax(): float
	{
		return $this->loadMax;
	}
}