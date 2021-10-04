<?php

namespace platz1de\EasyEdit\utils;

class AdditionalDataManager
{
	private bool $firstPiece = true;
	private bool $finalPiece = false;
	private bool $saveEditedChunks;
	private bool $saveUndo;

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
}