<?php

namespace platz1de\EasyEdit\task;

trait EditThreadExclusive
{
	public function calculateEffectiveComplexity(): int
	{
		return 0;
	}

	public function canExecuteOnMainThread(): bool
	{
		return false;
	}
}