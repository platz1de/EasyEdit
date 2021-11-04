<?php

namespace platz1de\EasyEdit\schematic;

use platz1de\EasyEdit\thread\EditThread;
use pocketmine\utils\Internet;
use Throwable;

class BlockConvertor
{
	/**
	 * @var array<int, int>
	 */
	private static array $idConversions;
	/**
	 * @var array<int, array<int, int>>
	 */
	private static array $metaConversions;

	public static function load(string $dataSource): void
	{
		self::$idConversions = [];
		self::$metaConversions = [];

		//This should only be executed on edit thread
		$result = Internet::getURL($dataSource, 10, [], $err);
		if ($result === null) {
			EditThread::getInstance()->getLogger()->error("Failed to load conversion data, schematic conversion is not available");
			if (isset($err)) {
				EditThread::getInstance()->getLogger()->logException($err);
			}
			return;
		}

		try {
			$data = json_decode($result->getBody(), true, 512, JSON_THROW_ON_ERROR);
			$replaceData = $data["replace"];
			$translateData = $data["translate"];
		} catch (Throwable $e) {
			EditThread::getInstance()->getLogger()->error("Failed to parse conversion data, schematic conversion is not available");
			EditThread::getInstance()->getLogger()->logException($e);
			return;
		}

		foreach ($replaceData as $java => $bedrock) {
			self::$idConversions[(int) $java] = (int) $bedrock;
		}

		foreach ($translateData as $bedrockId => $values) {
			$type = [];
			foreach ($values as $javaMeta => $bedrockMeta) {
				$type[(int) $javaMeta] = (int) $bedrockMeta;
			}
			self::$metaConversions[(int) $bedrockId] = $type;
		}
	}

	/**
	 * @param int $id
	 * @param int $meta
	 */
	public static function convert(int &$id, int &$meta): void
	{
		$id = self::$idConversions[$id] ?? $id;
		$meta = self::$metaConversions[$id][$meta] ?? $meta;
	}
}