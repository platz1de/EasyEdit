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
		return self::$selections[$player];
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