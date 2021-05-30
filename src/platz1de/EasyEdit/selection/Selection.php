<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use platz1de\EasyEdit\task\WrongSelectionTypeError;
use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Server;
use RuntimeException;
use Serializable;
use UnexpectedValueException;

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
	 * @var bool
	 */
	protected $piece;

	/**
	 * Selection constructor.
	 * @param string       $player
	 * @param string       $level
	 * @param Vector3|null $pos1
	 * @param Vector3|null $pos2
	 * @param bool         $piece
	 */
	public function __construct(string $player, string $level = "", ?Vector3 $pos1 = null, ?Vector3 $pos2 = null, bool $piece = false)
	{
		try {
			$this->level = Server::getInstance()->getLevelByName($level);
			if ($this->level === null) {
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

		$this->player = $player;
		$this->piece = $piece;

		$this->update();
	}

	/**
	 * @param Position $place
	 * @return Chunk[]
	 */
	abstract public function getNeededChunks(Position $place): array;

	/**
	 * @return Vector3
	 */
	public function getRealSize(): Vector3
	{
		return $this->getCubicEnd()->subtract($this->getCubicStart())->add(1, 1, 1);
	}

	/**
	 * @return Vector3
	 */
	public function getCubicStart(): Vector3
	{
		return $this->getPos1();
	}

	/**
	 * @return Vector3
	 */
	public function getCubicEnd(): Vector3
	{
		return $this->getPos2();
	}

	/**
	 * @return Vector3
	 */
	public function getSize(): Vector3
	{
		return $this->getPos2()->subtract($this->getPos1())->add(1, 1, 1);
	}

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
			$minY = max(min($this->pos1->getY(), $this->pos2->getY()), 0);
			$maxY = min(max($this->pos1->getY(), $this->pos2->getY()), Level::Y_MASK);
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
		if (($expected !== null) && get_class($selection) !== $expected) {
			throw new WrongSelectionTypeError(get_class($selection), $expected);
		}
		if (!$selection->isValid()) {
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

	/**
	 * Splits the selection into smaller parts
	 * lower the impact of Chunk loading
	 * @return Selection[]
	 */
	public function split(): array
	{
		return [$this];
	}

	/**
	 * @return array
	 */
	public function getData(): array
	{
		return [
			"player" => $this->player,
			"level" => is_string($this->level) ? $this->level : $this->level->getFolderName(),
			"minX" => $this->pos1->getX(),
			"minY" => $this->pos1->getY(),
			"minZ" => $this->pos1->getZ(),
			"maxX" => $this->pos2->getX(),
			"maxY" => $this->pos2->getY(),
			"maxZ" => $this->pos2->getZ()
		];
	}

	/**
	 * @param array $data
	 */
	public function setData(array $data): void
	{
		$this->player = $data["player"];
		try {
			$this->level = Server::getInstance()->getLevelByName($data["level"]) ?? $data["level"];
		} catch (RuntimeException $exception) {
			$this->level = $data["level"];
		}
		$this->pos1 = new Vector3($data["minX"], $data["minY"], $data["minZ"]);
		$this->pos2 = new Vector3($data["maxX"], $data["maxY"], $data["maxZ"]);
	}

	/**
	 * @return string
	 */
	public function serialize(): string
	{
		return igbinary_serialize($this->getData());
	}

	/**
	 * @param string $data
	 */
	public function unserialize($data): void
	{
		$this->setData(igbinary_unserialize($data));
	}
}