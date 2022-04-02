<?php

namespace platz1de\EasyEdit\task\editing\selection\pattern;

use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\task\editing\selection\SelectionEditTask;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\math\Vector3;

abstract class PatternedEditTask extends SelectionEditTask
{
	protected Pattern $pattern;

	/**
	 * @param PatternedEditTask $instance
	 * @param Selection         $selection
	 * @param Vector3           $splitOffset
	 * @param Pattern           $pattern
	 */
	public static function initPattern(PatternedEditTask $instance, Selection $selection, Vector3 $splitOffset, Pattern $pattern): void
	{
		SelectionEditTask::initSelection($instance, $selection, $splitOffset);
		$instance->pattern = $pattern;
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		parent::putData($stream);
		$stream->putString($this->pattern->fastSerialize());
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		parent::parseData($stream);
		$this->pattern = Pattern::fastDeserialize($stream->getString());
	}

	/**
	 * @return Pattern
	 */
	public function getPattern(): Pattern
	{
		return $this->pattern;
	}
}