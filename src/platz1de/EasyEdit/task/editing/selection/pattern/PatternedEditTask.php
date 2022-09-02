<?php

namespace platz1de\EasyEdit\task\editing\selection\pattern;

use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\pattern\PatternWrapper;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\task\editing\selection\SelectionEditTask;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

abstract class PatternedEditTask extends SelectionEditTask
{
	protected Pattern $pattern;

	/**
	 * @param Selection             $selection
	 * @param Pattern               $pattern
	 * @param SelectionContext|null $context
	 */
	public function __construct(Selection $selection, Pattern $pattern, ?SelectionContext $context = null)
	{
		$pattern = PatternWrapper::wrap([$pattern]);
		$this->pattern = $pattern;
		if ($context === null) {
			$context = $pattern->getSelectionContext();
		}
		parent::__construct($selection, $context);
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