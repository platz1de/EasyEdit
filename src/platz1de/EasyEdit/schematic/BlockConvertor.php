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
	private static array $conversionFrom;
	/**
	 * @var array<int, array<int, array{int, int}>>
	 */
	private static array $conversionTo;

	public static function load(string $dataSource): void
	{
		self::$conversionFrom = [];
		self::$conversionTo = [];

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
			$invalidJava = $data["invalid-java"];
			$invalidBedrock = $data["invalid-bedrock"];
		} catch (Throwable $e) {
			EditThread::getInstance()->getLogger()->error("Failed to parse conversion data, schematic conversion is not available");
			EditThread::getInstance()->getLogger()->logException($e);
			return;
		}

		//mappings of java block ids to bedrock block ids
		foreach ($replaceData as $javaId => $bedrockId) {
			for ($i = 0; $i < (1 << Block::INTERNAL_METADATA_BITS); $i++) {
				self::$conversionFrom[(int) $javaId][$i] = [(int) $bedrockId, $i];
				self::$conversionTo[(int) $bedrockId][$i] = [(int) $javaId, $i];
			}
		}

		//mappings of java metadata to bedrock metadata
		foreach ($translateData as $javaId => $values) {
			foreach ($values as $javaMeta => $bedrockMeta) {
				self::$conversionFrom[(int) $javaId][(int) $javaMeta] = [(int) $javaId, (int) $bedrockMeta];
				self::$conversionTo[(int) $javaId][(int) $bedrockMeta] = [(int) $javaId, (int) $javaMeta];
			}
		}

		//mappings of java block ids to bedrock block ids with metadata
		foreach ($complexData as $javaId => $values) {
			foreach ($values as $javaMeta => $bedrockData) {
				self::$conversionFrom[(int) $javaId][(int) $javaMeta] = [(int) $bedrockData[0], (int) $bedrockData[1]];
				self::$conversionTo[(int) $bedrockData[0]][(int) $bedrockData[1]] = [(int) $javaId, (int) $javaMeta];
			}
		}

		//These blocks do not exist in java
		foreach ($invalidJava as $bedrockId) {
			for ($i = 0; $i < (1 << Block::INTERNAL_METADATA_BITS); $i++) {
				self::$conversionTo[(int) $bedrockId][$i] = [0, 0];
			}
		}

		//These blocks do not exist in bedrock
		foreach ($invalidBedrock as $javaId) {
			for ($i = 0; $i < (1 << Block::INTERNAL_METADATA_BITS); $i++) {
				self::$conversionFrom[(int) $javaId][$i] = [0, 0];
			}
		}
	}

	/**
	 * @param int $id
	 * @param int $meta
	 */
	public static function convertFromJava(int &$id, int &$meta): void
	{
		[$id, $meta] = self::$conversionFrom[$id][$meta] ?? [$id, $meta];
	}

	/**
	 * @param int $id
	 * @param int $meta
	 */
	public static function convertToJava(int &$id, int &$meta): void
	{
		[$id, $meta] = self::$conversionTo[$id][$meta] ?? [$id, $meta];
	}
}