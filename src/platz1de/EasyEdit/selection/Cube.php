<?php

namespace platz1de\EasyEdit\selection;

use Closure;
use platz1de\EasyEdit\Messages;
use platz1de\EasyEdit\selection\constructor\CubicConstructor;
use platz1de\EasyEdit\selection\cubic\CubicChunkLoader;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\VectorUtils;
use platz1de\EasyEdit\world\HighlightingManager;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\World;
use Thread;
use Throwable;
use UnexpectedValueException;

class Cube extends Selection implements Patterned
{
	use CubicChunkLoader;

	private int $structure = 0;

	public function update(): void
	{
		parent::update();
		if (!$this->piece && $this->isValid() && !Thread::getCurrentThread() instanceof EditThread) {
			$this->close();
			$this->structure = HighlightingManager::highlightStaticCube($this->getPlayer(), $this->getWorld()->getFolderName(), $this->pos1, $this->pos2, new Vector3(floor(($this->pos2->getX() + $this->pos1->getX()) / 2), World::Y_MIN, floor(($this->pos2->getZ() + $this->pos1->getZ()) / 2)));
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
		if (isset($this->structure)) {
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
	 * @param Closure          $closure
	 * @param SelectionContext $context
	 * @param Selection        $full
	 */
	public function useOnBlocks(Closure $closure, SelectionContext $context, Selection $full): void
	{
		if ($context->isEmpty()) {
			return;
		}

		if ($context->isFull()) {
			CubicConstructor::betweenPoints($this->getPos1(), $this->getPos2(), $closure);
			return;
		}

		if ($context->includesFilling()) {
			//This can also make the selection larger (1x1 -> -3x-3), so we are not allowed to actually check for the smaller/larger position
			CubicConstructor::betweenPoints(Vector3::maxComponents($full->getCubicStart()->add(1, 1, 1), $this->getCubicStart()), Vector3::minComponents($full->getCubicEnd()->subtract(1, 1, 1), $this->getCubicEnd()), $closure);
		}

		if ($context->includesAllSides()) {
			CubicConstructor::onSides($this->getPos1(), $this->getPos2(), Facing::ALL, $context->getSideThickness(), $closure);
		} elseif ($context->includesWalls()) {
			CubicConstructor::onSides($this->getPos1(), $this->getPos2(), Facing::HORIZONTAL, $context->getSideThickness(), $closure);
		}

		if ($context->includesCenter()) {
			CubicConstructor::betweenPoints(Vector3::maxComponents($full->getPos1()->addVector($full->getPos2())->divide(2)->floor(), $this->getPos1()), Vector3::minComponents($full->getPos1()->addVector($full->getPos2())->divide(2)->ceil(), $this->getPos2()), $closure);
		}
	}

	/**
	 * @param Player $player
	 * @return Cube
	 */
	public static function createInWorld(Player $player): Cube
	{
		try {
			$selection = SelectionManager::getFromPlayer($player->getName());
			if ($selection instanceof self && $selection->getWorld() === $player->getWorld()) {
				return $selection;
			}
			$selection->close();
		} catch (Throwable) { //no selection
		}
		$selection = new Cube($player->getName(), $player->getWorld()->getFolderName(), null, null);
		SelectionManager::setForPlayer($player->getName(), $selection);
		return $selection;
	}

	/**
	 * @param Player  $player
	 * @param Vector3 $position
	 */
	public static function selectPos1(Player $player, Vector3 $position): void
	{
		$selection = self::createInWorld($player);

		$selection->setPos1($position->floor());

		Messages::send($player, "selected-pos1", ["{x}" => (string) $position->getX(), "{y}" => (string) $position->getY(), "{z}" => (string) $position->getZ()]);
	}

	/**
	 * @param Player  $player
	 * @param Vector3 $position
	 */
	public static function selectPos2(Player $player, Vector3 $position): void
	{
		$selection = self::createInWorld($player);

		$selection->setPos2($position->floor());

		Messages::send($player, "selected-pos2", ["{x}" => (string) $position->getX(), "{y}" => (string) $position->getY(), "{z}" => (string) $position->getZ()]);
	}
}