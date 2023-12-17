<?php

namespace platz1de\EasyEdit\task\editing;

use Generator;
use platz1de\EasyEdit\selection\constructor\ShapeConstructor;

interface ChunkedTask
{
	/**
	 * @param EditTaskHandler $handler
	 * @return Generator<ShapeConstructor>
	 */
	public function prepareConstructors(EditTaskHandler $handler): Generator;
}