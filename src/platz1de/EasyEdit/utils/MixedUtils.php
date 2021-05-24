<?php

namespace platz1de\EasyEdit\utils;

class MixedUtils
{
	/**
	 * @param string $int
	 * @param bool   $data
	 * @return string
	 */
	public static function humanReadable(string $int, bool $data = false): string
	{
		$factor = $data ? floor(log($int, 1024)) : floor((strlen($int) - 1) / 3);
		$string = $data ? number_format($int / 1024 ** $factor) : number_format($int);
		$unit = $data ? ["B", "KB", "MB", "GB", "TB"] : ["", "K", "M", "G", "T"];
		return substr($string, 0, strpos($string, ",") < 3 ? 4 : 3) . $unit[$factor];
	}
}