<?php

namespace platz1de\EasyEdit\selection;

use Exception;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\selection\cubic\CubicChunkLoader;
use platz1de\EasyEdit\selection\cubic\CubicIterator;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\HighlightingManager;
use platz1de\EasyEdit\utils\VectorUtils;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\World;
use UnexpectedValueException;

class Cube extends Selection implements Patterned
{
	use CubicIterator;
	use CubicChunkLoader;

	/**
	 * @var int
	 */
	private $structure = 0;

	public function update(): void
	{
		if ($this->isValid()) {
			$minX = min($this->pos1->getX(), $this->pos2->getX());
			$maxX = max($this->pos1->getX(), $this->pos2->getX());
			$minY = max(min($this->pos1->getY(), $this->pos2->getY()), World::Y_MIN);
			$maxY = min(max($this->pos1->getY(), $this->pos2->getY()), World::Y_MAX - 1);
			$minZ = min($this->pos1->getZ(), $this->pos2->getZ());
			$maxZ = max($this->pos1->getZ(), $this->pos2->getZ());

			$this->pos1 = new Vector3($minX, $minY, $minZ);
			$this->pos2 = new Vector3($maxX, $maxY, $maxZ);

			if (!$this->piece) {
				$this->close();
				$this->structure = HighlightingManager::highlightStaticCube($this->getPlayer(), $this->getWorld(), $this->pos1, $this->pos2, new Vector3(floor(($this->pos2->getX() + $this->pos1->getX()) / 2), World::Y_MIN, floor(($this->pos2->getZ() + $this->pos1->getZ()) / 2)));
			}
		}
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);

		$stream->putInt($this->structure);
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);

		$this->structure = $stream->getInt();
	}

	public function close(): void
	{
		if ($this->structure !== null) {
			HighlightingManager::clear($this->getPlayer(), $this->structure);
		}
	}

	/**
	 * splits into 3x3 Chunk pieces
	 * @param Vector3 $offset
	 * @return Cube[]
	 */
	public function split(Vector3 $offset): array
	{
		if ($this->piece) {
			throw new UnexpectedValueException("Pieces are not split able");
		}

		$min = VectorUtils::enforceHeight($this->pos1->addVector($offset));
		$max = VectorUtils::enforceHeight($this->pos2->addVector($offset));

		$pieces = [];
		for ($x = $min->getX() >> 4; $x <= $max->getX() >> 4; $x += 3) {
			for ($z = $min->getZ() >> 4; $z <= $max->getZ() >> 4; $z += 3) {
				$pieces[] = new Cube($this->getPlayer(), $this->getWorldName(), new Vector3(max(($x << 4) - $offset->getX(), $this->pos1->getX()), $this->pos1->getY(), max(($z << 4) - $offset->getZ(), $this->pos1->getZ())), new Vector3(min((($x + 2) << 4) + 15 - $offset->getX(), $this->pos2->getX()), $this->pos2->getY(), min((($z + 2) << 4) + 15 - $offset->getZ(), $this->pos2->getZ())), true);
			}
		}
		return $pieces;
	}

	/**
	 * @param Player  $player
	 * @param Vector3 $position
	 */
	public static function selectPos1(Player $player, Vector3 $position): void
	{
		try {
			$selection = SelectionManager::getFromPlayer($player->getName());
			if (!$selection instanceof self || $selection->getWorld() !== $player->getWorld()) {
				$selection->close();
				$selection = new Cube($player->getName(), $player->getWorld()->getFolderName());
			}
		} catch (Exception $exception) {
			$selection = new Cube($player->getName(), $player->getWorld()->getFolderName());
		}

		$selection->setPos1($position->floor());

		SelectionManager::setForPlayer($player->getName(), $selection);

		Messages::send($player, "selected-pos1", ["{x}" => (string) $position->getX(), "{y}" => (string) $position->getY(), "{z}" => (string) $position->getZ()]);
	}

	/**
	 * @param Player  $player
	 * @param Vector3 $position
	 */
	public static function selectPos2(Player $player, Vector3 $position): void
	{
		try {
			$selection = SelectionManager::getFromPlayer($player->getName());
			if (!$selection instanceof self || $selection->getWorld() !== $player->getWorld()) {
				$selection->close();
				$selection = new Cube($player->getName(), $player->getWorld()->getFolderName());
			}
		} catch (Exception $exception) {
			$selection = new Cube($player->getName(), $player->getWorld()->getFolderName());
		}

		$selection->setPos2($position->floor());

		SelectionManager::setForPlayer($player->getName(), $selection);

		Messages::send($player, "selected-pos2", ["{x}" => (string) $position->getX(), "{y}" => (string) $position->getY(), "{z}" => (string) $position->getZ()]);
	}
}