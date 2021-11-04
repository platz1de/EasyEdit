<?php

namespace platz1de\EasyEdit\schematic;

use platz1de\EasyEdit\thread\EditThread;
use pocketmine\block\Block;
use pocketmine\utils\Internet;
use Throwable;

class BlockConvertor
{
	/**
	 * @var array<int, array<int, array{int, int}>>
	 */
	private static array $conversionData;

	public static function load(string $dataSource): void
	{
		self::$conversionData = [];

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
			$complexData = $data["complex"];
		} catch (Throwable $e) {
			EditThread::getInstance()->getLogger()->error("Failed to parse conversion data, schematic conversion is not available");
			EditThread::getInstance()->getLogger()->logException($e);
			return;
		}

		foreach ($replaceData as $java => $bedrock) {
			for ($i = 0; $i < (1 << Block::INTERNAL_METADATA_BITS); $i++) {
				self::$conversionData[(int) $java][$i] = [(int) $bedrock, $i];
			}
		}

		foreach ($translateData as $javaId => $values) {
			foreach ($values as $javaMeta => $bedrockMeta) {
				self::$conversionData[(int) $javaId][(int) $javaMeta] = [(int) $javaId, (int) $bedrockMeta];
			}
		}

		foreach ($complexData as $javaId => $values) {
			foreach ($values as $javaMeta => $bedrockData) {
				self::$conversionData[(int) $javaId][(int) $javaMeta] = [(int) $bedrockData[0], (int) $bedrockData[1]];
			}
		}
	}

	/**
	 * @param int $id
	 * @param int $meta
	 */
	public static function convert(int &$id, int &$meta): void
	{
		[$id, $meta] = self::$conversionData[$id][$meta] ?? [$id, $meta];
	}
}