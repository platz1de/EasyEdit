<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use UnexpectedValueException;
use platz1de\EasyEdit\task\WrongSelectionTypeError;
use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Server;
use RuntimeException;
use Serializable;

abstract class Selection implements Serializable
{
	/**
	 * @var Level|string
	 */
	protected $level;

	/**
	 * @var Vector3
	 */
	protected $pos1;
	/**
	 * @var Vector3
	 */
	protected $pos2;
	/**
	 * @var Vector3
	 */
	protected $selected1;
	/**
	 * @var Vector3
	 */
	protected $selected2;

	/**
	 * @var string
	 */
	protected $player;

	/**
	 * Selection constructor.
	 * @param string       $player
	 * @param string       $level
	 * @param Vector3|null $pos1
	 * @param Vector3|null $pos2
	 */
	public function __construct(string $player, string $level = "", ?Vector3 $pos1 = null, ?Vector3 $pos2 = null)
	{
		try {
			$this->level = Server::getInstance()->getLevelByName($level);
			if($this->level === null){
				$this->level = $level;
			}
		} catch (RuntimeException $exception) {
			$this->level = $level;
		}

		if ($pos1 !== null) {
			$this->pos1 = clone($this->selected1 = $pos1);
		}
		if ($pos2 !== null) {
			$this->pos2 = clone($this->selected2 = $pos2);
		}

		$this->update();

		$this->player = $player;
	}

	/**
	 * @param Position $place
	 * @return Chunk[]
	 */
	abstract public function getNeededChunks(Position $place): array;

	/**
	 * @param Vector3 $place
	 * @param Closure $closure
	 * @return void
	 */
	abstract public function useOnBlocks(Vector3 $place, Closure $closure): void;

	/**
	 * @return bool
	 */
	public function isValid(): bool
	{
		return isset($this->pos1, $this->pos2);
	}

	/**
	 * calculating the "real" positions (selected ones don't have to be the smallest and biggest
	 * they could be mixed)
	 */
	protected function update(): void
	{
		if ($this->isValid()) {
			$minX = min($this->pos1->getX(), $this->pos2->getX());
			$maxX = max($this->pos1->getX(), $this->pos2->getX());
			$minY = min($this->pos1->getY(), $this->pos2->getY());
			$maxY = max($this->pos1->getY(), $this->pos2->getY());
			$minZ = min($this->pos1->getZ(), $this->pos2->getZ());
			$maxZ = max($this->pos1->getZ(), $this->pos2->getZ());

			$this->pos1->setComponents($minX, $minY, $minZ);
			$this->pos2->setComponents($maxX, $maxY, $maxZ);
		}
	}

	/**
	 * @param Vector3 $pos1
	 */
	public function setPos1(Vector3 $pos1): void
	{
		$this->pos1 = clone($this->selected1 = $pos1);
		if ($this->selected2 !== null) {
			$this->pos2 = clone($this->selected2);
		}

		$this->update();
	}

	/**
	 * @param Vector3 $pos2
	 */
	public function setPos2(Vector3 $pos2): void
	{
		if ($this->selected1 !== null) {
			$this->pos1 = clone($this->selected1);
		}
		$this->pos2 = clone($this->selected2 = $pos2);

		$this->update();
	}

	/**
	 * @return Level|string
	 */
	public function getLevel()
	{
		return $this->level;
	}

	/**
	 * @return Vector3
	 */
	public function getPos1(): Vector3
	{
		return $this->pos1;
	}

	/**
	 * @return Vector3
	 */
	public function getPos2(): Vector3
	{
		return $this->pos2;
	}

	/**
	 * @return string
	 */
	public function getPlayer(): string
	{
		return $this->player;
	}

	public function close(): void
	{
	}

	/**
	 * @param Selection   $selection
	 * @param string|null $expected
	 */
	public static function validate(Selection $selection, ?string $expected = null): void
	{
		if(($expected !== null) && get_class($selection) !== $expected) {
			throw new WrongSelectionTypeError(get_class($selection), $expected);
		}
		if(!$selection->isValid()){
			throw new UnexpectedValueException("Selection is not valid");
		}
	}

	/**
	 * @param int $blockId
	 * @return bool
	 */
	public static function processBlock(int &$blockId): bool
	{
		$return = ($blockId !== 0);

		if ($blockId === 217) {
			$blockId = 0;
		}

		return $return;
	}
}