<?php

namespace platz1de\EasyEdit\utils;

use Closure;
use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\task\editing\EditTask;

class AdditionalDataManager
{
	private bool $firstPiece = true;
	private bool $finalPiece = false;
	private bool $saveEditedChunks;
	private bool $saveUndo;
	private bool $useFastSet = false;
	/**
	 * @var Closure(EditTask, ?StoredSelectionIdentifier):void
	 */
	private Closure $resultHandler;

	/**
	 * Used in count tasks
	 * @var int[]
	 */
	private array $countedBlocks = [];

	/**
	 * @param bool $saveEditedChunks
	 * @param bool $saveUndo
	 */
	public function __construct(bool $saveEditedChunks, bool $saveUndo)
	{
		$this->saveEditedChunks = $saveEditedChunks;
		$this->saveUndo = $saveUndo;
	}

	/**
	 * @return bool
	 */
	public function isFirstPiece(): bool
	{
		return $this->firstPiece;
	}

	/**
	 * @return bool
	 */
	public function isFinalPiece(): bool
	{
		return $this->finalPiece;
	}

	public function donePiece(): void
	{
		$this->firstPiece = false;
	}

	public function setFinal(): void
	{
		$this->finalPiece = true;
	}

	/**
	 * @return bool
	 */
	public function isSavingChunks(): bool
	{
		return $this->saveEditedChunks;
	}

	/**
	 * @return bool
	 */
	public function isSavingUndo(): bool
	{
		return $this->saveUndo;
	}

	public function useFastSet(): void
	{
		$this->useFastSet = true;
	}

	/**
	 * @return bool
	 */
	public function isUsingFastSet(): bool
	{
		return $this->useFastSet;
	}

	/**
	 * @return int[]
	 */
	public function getCountedBlocks(): array
	{
		return $this->countedBlocks;
	}

	/**
	 * @param int[] $countedBlocks
	 */
	public function setCountedBlocks(array $countedBlocks): void
	{
		$this->countedBlocks = $countedBlocks;
	}

	/**
	 * @return bool
	 */
	public function hasResultHandler(): bool
	{
		return isset($this->resultHandler);
	}

	/**
	 * @return Closure(EditTask, ?StoredSelectionIdentifier):void
	 */
	public function getResultHandler(): Closure
	{
		return $this->resultHandler;
	}

	/**
	 * @param Closure(EditTask, ?StoredSelectionIdentifier):void $resultHandler
	 */
	public function setResultHandler(Closure $resultHandler): void
	{
		$this->resultHandler = $resultHandler;
	}

	/**
	 * @return string
	 */
	public function fastSerialize(): string
	{
		$stream = new ExtendedBinaryStream();
		$stream->putBool($this->saveEditedChunks);
		$stream->putBool($this->saveUndo);

		$count = 0;
		foreach ($this->countedBlocks as $id => $blockCount) {
			$stream->putInt($id);
			$stream->putInt($blockCount);
			$count++;
		}
		$stream->putInt($count);

		return $stream->getBuffer();
	}

	/**
	 * @param string $data
	 * @return AdditionalDataManager
	 */
	public static function fastDeserialize(string $data): AdditionalDataManager
	{
		$stream = new ExtendedBinaryStream($data);
		$dataManager = new AdditionalDataManager($stream->getBool(), $stream->getBool());
		$count = $stream->getInt();

		$counted = [];
		for ($i = 0; $i < $count; $i++) {
			/** @noinspection AmbiguousMethodsCallsInArrayMappingInspection */
			$counted[$stream->getInt()] = $stream->getInt();
		}
		$dataManager->setCountedBlocks($counted);

		return $dataManager;
	}
}