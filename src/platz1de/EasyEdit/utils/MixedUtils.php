<?php

namespace platz1de\EasyEdit\utils;

class MixedUtils
{
	/**
	 * @param string $int
	 * @return string
	 */
	public static function humanReadable(string $int): string
	{
		$factor = floor((strlen($int) - 1) / 3);
		return substr($n = number_format($int), 0, strpos($n, ",") < 3 ? 4 : 3) . ["", "k", "M", "G", "T"][$factor];
	}
}