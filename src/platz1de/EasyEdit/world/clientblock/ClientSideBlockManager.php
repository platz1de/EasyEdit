<?php

namespace platz1de\EasyEdit\world\clientblock;

use pocketmine\player\Player;
use pocketmine\Server;

class ClientSideBlockManager
{
	/**
	 * @var array<string, array<int, ClientSideBlock>>
	 */
	private static array $blocks = [];

	/**
	 * @param string          $player
	 * @param ClientSideBlock $block
	 * @return int
	 */
	public static function registerBlock(string $player, ClientSideBlock $block): int
	{
		self::$blocks[$player][$block->getId()] = $block;
		if (($p = Server::getInstance()->getPlayerExact($player)) instanceof Player) {
			$block->send($p);
		}
		return $block->getId();
	}

	/**
	 * @param string $player
	 * @param int    $id
	 */
	public static function unregisterBlock(string $player, int $id): void
	{
		if (($p = Server::getInstance()->getPlayerExact($player)) instanceof Player) {
			self::$blocks[$player][$id]->remove($p);
		}
		unset(self::$blocks[$player][$id]);
	}

	/**
	 * @param string $player
	 */
	public static function resendAll(string $player): void
	{
		if (isset(self::$blocks[$player]) && (($p = Server::getInstance()->getPlayerExact($player)) instanceof Player)) {
			foreach (self::$blocks[$player] as $block) {
				$block->checkResend($p);
			}
		}
	}

	/**
	 * Should be called when a player does anything that could change the block
	 * @param Player $player
	 */
	public static function updateAll(Player $player): void
	{
		if (isset(self::$blocks[$player->getName()])) {
			foreach (self::$blocks[$player->getName()] as $block) {
				$block->update($player);
			}
		}
	}
}