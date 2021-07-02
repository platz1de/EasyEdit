<?php

namespace platz1de\EasyEdit\selection;

class ClipBoardManager
{
	/**
	 * @var DynamicBlockListSelection[]
	 */
	private static $selections = [];

	/**
	 * @param string $player
	 * @return DynamicBlockListSelection
	 */
	public static function getFromPlayer(string $player): DynamicBlockListSelection
	{
		$selection = new DynamicBlockListSelection($player);
		$selection->setPos2(self::$selections[$player]->getPos2());
		$selection->setPoint(self::$selections[$player]->getPoint());
		foreach (self::$selections[$player]->getManager()->getChunks() as $chunk) {
			$selection->getManager()->setChunk($chunk->getX(), $chunk->getZ(), $chunk);
		}
		foreach (self::$selections[$player]->getTiles() as $tile) {
			$selection->addTile($tile);
		}
		return $selection;
	}

	/**
	 * @param string                    $player
	 * @param DynamicBlockListSelection $selection
	 */
	public static function setForPlayer(string $player, DynamicBlockListSelection $selection): void
	{
		self::$selections[$player] = $selection;
	}
}